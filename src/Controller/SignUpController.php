<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\OrderRepository;
use App\Repository\SettingRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SignUpController extends BaseController
{
    private UserRepository $userRepository;

    private OrderRepository $orderRepository;

    /**
     * @param CategoryRepository $categoryRepository
     * @param SettingRepository $settingRepository
     * @param UserRepository $userRepository
     * @param OrderRepository $orderRepository
     */
    public function __construct(
        CategoryRepository $categoryRepository,
        SettingRepository $settingRepository,
        UserRepository $userRepository,
        OrderRepository $orderRepository
    ) {
        parent::__construct($categoryRepository, $settingRepository);
        $this->userRepository = $userRepository;
        $this->orderRepository = $orderRepository;
    }

    public function index(): Response
    {
        return $this->renderTemplate('sign_up/index.html.twig', []);
    }

    public function post(Request $request): Response
    {
        if ($this->userRepository->findOneBy(['email' => $request->request->get('email')])) {
            return new Response(json_encode([
                'error' => 'Email already exists'
            ]));
        }

        $user = $this->userRepository->create([
            'email' => $request->request->get('email'),
            'name' => $request->request->get('name'),
            'surname' => $request->request->get('surname'),
            'phone' => $request->request->get('phone'),
            'password' => $request->request->get('password')
        ]);

        $orders = $this->orderRepository->findBy(['email' => $request->request->get('email')]);

        foreach ($orders as $order) {
            $order->setUser($user);
            $this->orderRepository->update($order);
        }

        $session = $request->getSession();

        $session->set('user', $request->request->get('email'));

        return new Response(json_encode([
            'success' => true
        ]));
    }
}
