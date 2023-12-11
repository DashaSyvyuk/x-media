<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\SettingRepository;
use App\Repository\UserRepository;
use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RestorePasswordController extends BaseController
{
    /**
     * @param CategoryRepository $categoryRepository
     * @param SettingRepository $settingRepository
     * @param UserRepository $userRepository
     * @param ProductRepository $productRepository
     */
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly SettingRepository $settingRepository,
        private readonly UserRepository $userRepository,
        private readonly ProductRepository $productRepository,
    ) {
        parent::__construct($this->categoryRepository, $this->settingRepository, $this->productRepository);
    }

    public function index(Request $request): Response
    {
        return $this->renderTemplate($request, 'restore_password/index.html.twig', []);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function post(Request $request, MailerInterface $mailer): Response
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

        $link = sprintf('%s%s/update-password?hash=%s', 'https://', $request->getHost(), $uuid);

        $message = (new Email())
            ->subject('Відновлення паролю')
            ->from('x-media@x-media.com.ua')
            ->to($email)
            ->html(
                $this->renderView(
                    'emails/restore-password.html.twig',
                    ['link' => $link]
                )
            )
        ;

        $mailer->send($message);

        return new Response(json_encode([
            'success' => true
        ]));
    }
}
