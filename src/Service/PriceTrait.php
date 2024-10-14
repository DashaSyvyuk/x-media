<?php

namespace App\Service;

use App\Entity\Feed;
use App\Entity\CategoryFeedPrice;
use App\Entity\Product;

trait PriceTrait
{
    private function getPrice(Product $product, ?Feed $feed, ?CategoryFeedPrice $priceParameters): float|int
    {
        if ($priceParameters) {
            return $this->adjustPrice($product->getPrice(), $priceParameters->getOurPercent(), $priceParameters->getFee());
        }

        return $this->adjustPrice($product->getPrice(), $feed->getOurPercent(), $feed->getFee());
    }

    private function adjustPrice(int $price, int $ourPercent, int $fee): float|int
    {
        $totalDiscount = $ourPercent + $fee; // Сума знижок
        $multiplier = 1 / (1 - $totalDiscount / 100);

        $newPrice = $price * $multiplier;

        if ($price < 20000) {
            // Округлення до сотень і віднімання 1
            $newPrice = ceil($newPrice / 100) * 100 - 1;
        } else {
            $newPrice = ceil($newPrice / 1000) * 1000 - 1;
        }

        return $newPrice;
    }
}
