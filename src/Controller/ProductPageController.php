<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\SettingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class ProductPageController extends AbstractController
{
    private CategoryRepository $categoryRepository;

    private ProductRepository $productRepository;

    private SettingRepository $settingRepository;

    /**
     * @param CategoryRepository $categoryRepository
     * @param ProductRepository $productRepository
     * @param SettingRepository $settingRepository
     */
    public function __construct(
        CategoryRepository $categoryRepository,
        ProductRepository $productRepository,
        SettingRepository $settingRepository
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->productRepository = $productRepository;
        $this->settingRepository = $settingRepository;
    }

    public function get(string $id): Response
    {
        $product = $this->productRepository->findOneBy(['id' => $id]);

        if (!$product) {
            $this->render('/not-found.html.twig');
        }

        return $this->render('product_page/index.html.twig', [
            'product' => $product,
            'categories' => $this->categoryRepository->findBy(['status' => 'ACTIVE'], ['position' => 'ASC']),
            'totalCount' => $_COOKIE['totalCount'] ?? 0,
            'phoneNumbers' => $this->settingRepository->findBy(['slug' => 'phone_number']),
            'emails' => $this->settingRepository->findBy(['slug' => 'email'])
        ]);
    }
}
