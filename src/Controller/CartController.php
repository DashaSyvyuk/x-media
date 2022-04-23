<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class CartController extends AbstractController
{
    private ProductRepository $productRepository;

    public function __construct(ProductRepository $productRepository) {
        $this->productRepository = $productRepository;
    }

    public function getCart(): Response
    {
        $products = [];
        $totalCount = 0;
        $totalPrice = 0;

        if (!empty($_COOKIE['cart'])) {
            foreach (json_decode($_COOKIE['cart']) as $item) {
                if ($item->id && $item->id > 0 && $item->count && $item->count > 0) {
                    $product = $this->productRepository->findOneBy(['id' => $item->id]);

                    if ($product) {
                        $product->count = $item->count;
                        $totalCount += $item->count;
                        $totalPrice += $product->getPrice() * $item->count;
                        $products[] = $product;
                    }
                }
            }
        }

        return new Response(json_encode([
            'cart' => $this->renderView('cart/index.html.twig', [
                'products' => $products,
                'totalCount' => $totalCount,
                'totalPrice' => $totalPrice
            ])
        ]));
    }
}
