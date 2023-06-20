<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\SettingRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CartController extends BaseController
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly SettingRepository $settingRepository,
        private readonly ProductRepository $productRepository
    ) {
        parent::__construct($this->categoryRepository, $this->settingRepository);
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

    public function getMobileCart(Request $request): Response
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

        return $this->renderTemplate($request, 'cart/index.html.twig', [
            'products' => $products,
            'totalCount' => $totalCount,
            'totalPrice' => $totalPrice
        ]);
    }
}
