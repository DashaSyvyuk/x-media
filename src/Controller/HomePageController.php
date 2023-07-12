<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\FeedbackRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\SettingRepository;
use App\Repository\SliderRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HomePageController extends BaseController
{
    private SliderRepository $sliderRepository;

    private ProductRepository $productRepository;

    private FeedbackRepository $feedbackRepository;

    /**
     * @param SliderRepository $sliderRepository
     * @param CategoryRepository $categoryRepository
     * @param ProductRepository $productRepository
     * @param SettingRepository $settingRepository
     * @param FeedbackRepository $feedbackRepository
     */
    public function __construct(
        SliderRepository $sliderRepository,
        CategoryRepository $categoryRepository,
        ProductRepository $productRepository,
        SettingRepository $settingRepository,
        FeedbackRepository $feedbackRepository,
        UserRepository $userRepository,
        OrderRepository $orderRepository
    ) {
        parent::__construct($categoryRepository, $settingRepository);
        $this->sliderRepository = $sliderRepository;
        $this->productRepository = $productRepository;
        $this->feedbackRepository = $feedbackRepository;
        $this->userRepository = $userRepository;
        $this->orderRepository = $orderRepository;
    }

    public function index(Request $request): Response
    {
        $session = $request->getSession();

        $email = $session->get('user');

        $user = $this->userRepository->findOneBy(['email' => $email]);

        return $this->renderTemplate($request, 'home_page/index.html.twig', [
            'sliders' => $this->sliderRepository->findBy([], ['priority' => 'ASC']),
            'products' => $this->productRepository->findBy(['status' => Product::STATUS_ACTIVE], ['createdAt' => 'DESC'], 10),
            'totalCount' => $_COOKIE['totalCount'] ?? 0,
            'feedbacks' => $this->feedbackRepository->findBy(['status' => 'CONFIRMED'], ['createdAt' => 'DESC']),
            'order' => $this->orderRepository->findOneBy(['user' => $user], ['createdAt' => 'DESC']),
            'user' => $user
        ]);
    }
}
