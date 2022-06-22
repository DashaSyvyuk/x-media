<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\SettingRepository;
use Symfony\Component\HttpFoundation\Response;

class ProductPageController extends BaseController
{
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
        parent::__construct($categoryRepository);
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
            'phoneNumbers' => $this->settingRepository->findBy(['slug' => 'phone_number']),
            'emails' => $this->settingRepository->findBy(['slug' => 'email'])
        ]);
    }
}
