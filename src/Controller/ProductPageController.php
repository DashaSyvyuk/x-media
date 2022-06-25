<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\SettingRepository;
use Symfony\Component\HttpFoundation\Response;

class ProductPageController extends BaseController
{
    private ProductRepository $productRepository;

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
        parent::__construct($categoryRepository, $settingRepository);
        $this->productRepository = $productRepository;
    }

    public function get(string $id): Response
    {
        $product = $this->productRepository->findOneBy(['id' => $id]);

        if (!$product) {
            $this->render('/not-found.html.twig');
        }

        return $this->render('product_page/index.html.twig', [
            'product' => $product
        ]);
    }
}
