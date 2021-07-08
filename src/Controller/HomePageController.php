<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\SliderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class HomePageController extends AbstractController
{
    private SliderRepository $sliderRepository;

    private CategoryRepository $categoryRepository;

    private ProductRepository $productRepository;

    /**
     * @param SliderRepository $sliderRepository
     * @param CategoryRepository $categoryRepository
     * @param ProductRepository $productRepository
     */
    public function __construct(
        SliderRepository $sliderRepository,
        CategoryRepository $categoryRepository,
        ProductRepository $productRepository
    ) {
        $this->sliderRepository = $sliderRepository;
        $this->categoryRepository = $categoryRepository;
        $this->productRepository = $productRepository;
    }

    public function index(): Response
    {
        $sliders = $this->sliderRepository->findBy([], ['priority' => 'DESC']);
        $categories = $this->categoryRepository->findBy([
            'status' => 'ACTIVE'
        ], [
            'position' => 'ASC'
        ]);
        $products = $this->productRepository->findBy([
            'status' => 'ACTIVE'
        ], [
            'createdAt' => 'DESC'
        ], 10);

        return $this->render('home_page/index.html.twig', [
            'sliders' => $sliders,
            'categories' => $categories,
            'products' => $products,
            'totalCount' => $_COOKIE['totalCount']
        ]);
    }
}
