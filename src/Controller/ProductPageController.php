<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class ProductPageController extends AbstractController
{
    private CategoryRepository $categoryRepository;

    private ProductRepository $productRepository;

    /**
     * @param CategoryRepository $categoryRepository
     * @param ProductRepository $productRepository
     */
    public function __construct(
        CategoryRepository $categoryRepository,
        ProductRepository $productRepository
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->productRepository = $productRepository;
    }

    public function get(string $id): Response
    {
        $product = $this->productRepository->findOneBy([
            'id' => $id
        ]);

        if (!$product) {
            $this->render('/not-found.html.twig');
        }

        $categories = $this->categoryRepository->findBy([
            'status' => 'ACTIVE'
        ], [
            'position' => 'ASC'
        ]);

        return $this->render('product_page/index.html.twig', [
            'product' => $product,
            'categories' => $categories
        ]);
    }
}
