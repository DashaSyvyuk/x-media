<?php

namespace App\Utils;

use App\Repository\OrderRepository;

class OrderNumber {

    /**
     * @param OrderRepository $orderRepository
     */
    public function __construct(
        private readonly OrderRepository $orderRepository,
    ) {
    }

    public function generateOrderNumber(): string
    {
        $orderNumber = (string) time();
        $order = $this->orderRepository->findOneBy(['orderNumber' => $orderNumber]);

        if ($order) {
            $orderNumber = $this->generateOrderNumber();
        }

        return $orderNumber;
    }
}
