<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\SettingRepository;
use App\Repository\UserRepository;
use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SignUpController extends BaseController
{
    /**
     * @param CategoryRepository $categoryRepository
     * @param SettingRepository $settingRepository
     * @param UserRepository $userRepository
     * @param OrderRepository $orderRepository
     * @param ProductRepository $productRepository
     */
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly SettingRepository $settingRepository,
        private readonly UserRepository $userRepository,
        private readonly OrderRepository $orderRepository,
        private readonly ProductRepository $productRepository,
    ) {
        parent::__construct($this->categoryRepository, $this->settingRepository, $this->productRepository);
    }

    public function index(Request $request): Response
    {
        return $this->renderTemplate($request, 'sign_up/index.html.twig', []);
    }

    public function post(Request $request, MailerInterface $mailer): Response
    {
        if ($this->userRepository->findOneBy(['email' => $request->request->get('email')])) {
            return new Response(json_encode([
                'error' => 'Email already exists'
            ]));
        }

        $uuid = Uuid::uuid4()->toString();

        $user = $this->userRepository->create([
            'email' => $request->request->get('email'),
            'name' => $request->request->get('name'),
            'surname' => $request->request->get('surname'),
            'phone' => $request->request->get('phone'),
            'password' => $request->request->get('password'),
            'confirmed' => false,
            'hash' => $uuid,
            'expiredAt' => Carbon::now()->addHour(),
        ]);

        $orders = $this->orderRepository->findBy(['email' => $request->request->get('email')]);

        foreach ($orders as $order) {
            $order->setUser($user);
            $this->orderRepository->update($order);
        }

        $link = sprintf('%s/confirm-email?hash=%s', $request->getHost(), $uuid);

        $message = (new Email())
            ->subject('Підтвердження email')
            ->from('x-media@x-media.com.ua')
            ->to($request->request->get('email'))
            ->html(
                $this->renderView(
                    'emails/confirm-email.html.twig',
                    ['link' => $link]
                ),
            )
        ;

        $mailer->send($message);

        return new Response(json_encode([
            'success' => true
        ]));
    }
}
