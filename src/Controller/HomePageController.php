<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\FeedbackRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\PromotionRepository;
use App\Repository\SettingRepository;
use App\Repository\SliderRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HomePageController extends BaseController
{
    /**
     * @param SliderRepository $sliderRepository
     * @param CategoryRepository $categoryRepository
     * @param ProductRepository $productRepository
     * @param SettingRepository $settingRepository
     * @param FeedbackRepository $feedbackRepository
     * @param UserRepository $userRepository
     * @param OrderRepository $orderRepository
     * @param PromotionRepository $promotionRepository
     */
    public function __construct(
        private readonly SliderRepository $sliderRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly ProductRepository $productRepository,
        private readonly SettingRepository $settingRepository,
        private readonly FeedbackRepository $feedbackRepository,
        private readonly UserRepository $userRepository,
        private readonly OrderRepository $orderRepository,
        private readonly PromotionRepository $promotionRepository,
    ) {
        parent::__construct($this->categoryRepository, $this->settingRepository, $this->productRepository);
    }

    public function index(Request $request): Response
    {
        $session = $request->getSession();

        $email = $session->get('user');

        $user = $this->userRepository->findOneBy(['email' => $email]);

        return $this->renderTemplate($request, 'home_page/index.html.twig', [
            'sliders'    => $this->sliderRepository->getActiveItems(),
            'products'   => $this->productRepository->findBy(
                ['status' => Product::STATUS_ACTIVE],
                ['createdAt' => 'DESC'],
                10
            ),
            'totalCount' => $_COOKIE['totalCount'] ?? 0,
            'feedbacks'  => $this->feedbackRepository->findBy(['status' => 'CONFIRMED'], ['createdAt' => 'DESC']),
            'order'      => $this->orderRepository->findOneBy(['user' => $user], ['createdAt' => 'DESC']),
            'user'       => $user,
            'promotions' => $this->promotionRepository->getActivePromotions(),
        ]);
    }
}
