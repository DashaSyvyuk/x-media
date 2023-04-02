<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\FeedbackRepository;
use App\Repository\ProductRepository;
use App\Repository\SettingRepository;
use App\Repository\SliderRepository;
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
        FeedbackRepository $feedbackRepository
    ) {
        parent::__construct($categoryRepository, $settingRepository);
        $this->sliderRepository = $sliderRepository;
        $this->productRepository = $productRepository;
        $this->feedbackRepository = $feedbackRepository;
    }

    public function index(Request $request): Response
    {
        return $this->renderTemplate($request, 'home_page/index.html.twig', [
            'sliders' => $this->sliderRepository->findBy([], ['priority' => 'ASC']),
            'products' => $this->productRepository->findBy(['status' => 'ACTIVE'], ['createdAt' => 'DESC'], 10),
            'totalCount' => $_COOKIE['totalCount'] ?? 0,
            'feedbacks' => $this->feedbackRepository->findBy(['status' => 'CONFIRMED'], ['createdAt' => 'DESC'])
        ]);
    }
}
