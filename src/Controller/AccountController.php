<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\CategoryRepository;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use App\Repository\SettingRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AccountController extends BaseController
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

        if (!$user) {
            return $this->redirectToRoute('index');
        }

        $order = $this->orderRepository->findOneBy(['user' => $user, 'status' => [
            Order::NEW,
            Order::NOT_CONFIRMED,
            Order::IN_PROGRESS,
            Order::ORDERED_IN_SUPPLIER,
            Order::ON_THE_WAY,
            Order::SENT_BY_NP,
            Order::SENT_BY_OUR_DELIVERY
        ]], ['createdAt' => 'DESC']);

        $text = $this->settingRepository->findOneBy(['slug' => 'there_is_no_active_order']);

        return $this->renderTemplate($request, 'account/index.html.twig', [
            'user' => $user,
            'order' => $order,
            'noOrder' => $text,
        ]);
    }
}
