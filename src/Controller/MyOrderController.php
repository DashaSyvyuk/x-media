<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use App\Repository\SettingRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MyOrderController extends BaseController
{
    private UserRepository $userRepository;

    private OrderRepository $orderRepository;

    private SettingRepository $settingRepository;

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
        $this->settingRepository = $settingRepository;
        $this->userRepository = $userRepository;
        $this->orderRepository = $orderRepository;
    }

    public function index(Request $request): Response
    {
        $session = $request->getSession();

        $email = $session->get('user');

        $user = $this->userRepository->findOneBy(['email' => $email]);

        $orders = $this->orderRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);

        $text = $this->settingRepository->findOneBy(['slug' => 'there_is_no_active_order']);

        if (!$user) {
            return $this->redirectToRoute('index');
        }

        return $this->renderTemplate($request, 'my_order/index.html.twig', [
            'user' => $user,
            'orders' => $orders,
            'noOrder' => $text,
        ]);
    }
}
