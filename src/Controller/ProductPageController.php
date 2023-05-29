<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\SettingRepository;
use Symfony\Component\HttpFoundation\Request;
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

    public function getProduct(string $id, Request $request): Response
    {
        $product = $this->productRepository->findOneBy(['id' => $id, 'status' => Product::STATUS_ACTIVE]);

        if (!$product) {
            return $this->redirectToRoute('index');
        }

        return $this->renderTemplate($request, 'product_page/index.html.twig', [
            'product' => $product
        ]);
    }
}
