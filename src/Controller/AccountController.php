<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use App\Repository\SettingRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AccountController extends BaseController
{
    /**
     * @param CategoryRepository $categoryRepository
     * @param SettingRepository $settingRepository
     * @param ProductRepository $productRepository
     * @param UserRepository $userRepository
     * @param OrderRepository $orderRepository
     */
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly SettingRepository $settingRepository,
        private readonly ProductRepository $productRepository,
        private readonly UserRepository $userRepository,
        private readonly OrderRepository $orderRepository
    ) {
        parent::__construct($this->categoryRepository, $this->settingRepository, $this->productRepository);
    }

    public function index(Request $request): Response
    {
        $session = $request->getSession();

        $email = $session->get('user');

        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            return $this->redirectToRoute('login');
        }

        $order = $this->orderRepository->findOneBy(['user' => $user], ['createdAt' => 'DESC']);

        $text = $this->settingRepository->findOneBy(['slug' => 'there_is_no_active_order']);

        return $this->renderTemplate($request, 'account/index.html.twig', [
            'user' => $user,
            'order' => $order,
            'noOrder' => $text,
            'monthNamesUa' => User::MONTH_NAMES,
        ]);
    }
}
