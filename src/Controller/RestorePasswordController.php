<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\SettingRepository;
use App\Repository\UserRepository;
use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use Swift_Mailer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RestorePasswordController extends BaseController
{
    private UserRepository $userRepository;

    /**
     * @param CategoryRepository $categoryRepository
     * @param SettingRepository $settingRepository
     * @param UserRepository $userRepository
     */
    public function __construct(
        CategoryRepository $categoryRepository,
        SettingRepository $settingRepository,
        UserRepository $userRepository
    ) {
        parent::__construct($categoryRepository, $settingRepository);
        $this->userRepository = $userRepository;
    }

    public function index(): Response
    {
        return $this->renderTemplate('restore_password/index.html.twig', []);
    }

    public function post(Request $request, Swift_Mailer $mailer): Response
    {
        $email = $request->request->get('email');
        $user = $this->userRepository->findOneBy([
            'email' => $email,
            'confirmed' => true
        ]);

        if (!$user) {
            return new Response(json_encode([
                'error' => 'Користувача з введеним email не існує'
            ]));
        }

        $uuid = Uuid::uuid4()->toString();

        $user->setHash($uuid);
        $user->setExpiredAt(Carbon::now()->addHour());
        $this->userRepository->update($user);

        $link = sprintf('%s/update-password?hash=%s', $request->getHost(), $uuid);

        $message = (new \Swift_Message('Відновлення паролю'))
            ->setFrom('x-media@x-media.com.ua')
            ->setTo($email)
            ->setBody(
                $this->renderView(
                    'emails/restore-password.html.twig',
                    ['link' => $link]
                ),
                'text/html'
            )
        ;

        $mailer->send($message);

        return new Response(json_encode([
            'success' => true
        ]));
    }
}
