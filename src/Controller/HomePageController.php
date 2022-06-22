<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\FeedbackRepository;
use App\Repository\ProductRepository;
use App\Repository\SettingRepository;
use App\Repository\SliderRepository;
use Symfony\Component\HttpFoundation\Response;

class HomePageController extends BaseController
{
    private SliderRepository $sliderRepository;

    private ProductRepository $productRepository;

    private SettingRepository $settingRepository;

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
        parent::__construct($categoryRepository);
        $this->sliderRepository = $sliderRepository;
        $this->productRepository = $productRepository;
        $this->settingRepository = $settingRepository;
        $this->feedbackRepository = $feedbackRepository;
    }

    public function index(): Response
    {
        return $this->renderTemplate('home_page/index.html.twig', [
            'sliders' => $this->sliderRepository->findBy([], ['priority' => 'ASC']),
            'products' => $this->productRepository->findBy(['status' => 'ACTIVE'], ['createdAt' => 'DESC'], 10),
            'totalCount' => $_COOKIE['totalCount'] ?? 0,
            'phoneNumbers' => $this->settingRepository->findBy(['slug' => 'phone_number']),
            'emails'  => $this->settingRepository->findBy(['slug' => 'email']),
            'feedbacks' => $this->feedbackRepository->findBy(['status' => 'CONFIRMED'], ['createdAt' => 'DESC'])
        ]);
    }
}
