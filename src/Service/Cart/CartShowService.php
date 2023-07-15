<?php

namespace App\Service\Cart;

use App\Repository\ProductRepository;

class CartShowService
{
    public function __construct(
        private readonly ProductRepository $productRepository
    ) {
    }

    public function run(string $items): array
    {
        $products = [];
        $totalCount = 0;
        $totalPrice = 0;

        if (!empty($items)) {
            foreach (json_decode($items) as $item) {
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

        return [
            'products' => $products,
            'totalCount' => $totalCount,
            'totalPrice' => $totalPrice
        ];
    }
}
