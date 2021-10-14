<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\SettingRepository;
use App\Repository\SliderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class HomePageController extends AbstractController
{
    private SliderRepository $sliderRepository;

    private CategoryRepository $categoryRepository;

    private ProductRepository $productRepository;

    private SettingRepository $settingRepository;

    /**
     * @param SliderRepository $sliderRepository
     * @param CategoryRepository $categoryRepository
     * @param ProductRepository $productRepository
     * @param SettingRepository $settingRepository
     */
    public function __construct(
        SliderRepository $sliderRepository,
        CategoryRepository $categoryRepository,
        ProductRepository $productRepository,
        SettingRepository $settingRepository
    ) {
        $this->sliderRepository = $sliderRepository;
        $this->categoryRepository = $categoryRepository;
        $this->productRepository = $productRepository;
        $this->settingRepository = $settingRepository;
    }

    public function index(): Response
    {
        return $this->render('home_page/index.html.twig', [
            'sliders'      => $this->sliderRepository->findBy([], ['priority' => 'DESC']),
            'categories'   => $this->categoryRepository->findBy(['status' => 'ACTIVE'], ['position' => 'ASC']),
            'products'     => $this->productRepository->findBy(['status' => 'ACTIVE'], ['createdAt' => 'DESC'], 10),
            'totalCount'   => $_COOKIE['totalCount'] ?? 0,
            'phoneNumbers' => $this->settingRepository->findBy(['slug' => 'phone_number']),
            'emails'       => $this->settingRepository->findBy(['slug' => 'email'])
        ]);
    }
}
