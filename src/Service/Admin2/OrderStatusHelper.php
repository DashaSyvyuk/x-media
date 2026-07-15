<?php

namespace App\Service\Admin2;

use App\Entity\Order;
use SM\Factory\Factory;
use SM\SMException;

final class OrderStatusHelper
{
    public function __construct(
        private readonly Factory $stateFactory,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function getAvailableStatuses(?Order $order): array
    {
        if ($order !== null && $order->getId()) {
            $statuses = [];
            $orderSM  = $this->stateFactory->get($order, 'simple');

            foreach (Order::STATUSES as $key => $label) {
                try {
                    if ($orderSM->can($key)) {
                        $statuses[$key] = $label;
                    }
                } catch (SMException) {
                }
            }

            if ($statuses !== []) {
                return $statuses;
            }
        }

        return Order::STATUSES;
    }

    /**
     * @throws \RuntimeException
     */
    public function changeStatus(Order $order, string $newStatus): void
    {
        if (! isset(Order::STATUSES[$newStatus])) {
            throw new \RuntimeException('Невідомий статус замовлення.');
        }

        $orderSM = $this->stateFactory->get($order, 'simple');

        try {
            if (! $orderSM->can($newStatus)) {
                throw new \RuntimeException(sprintf(
                    'Неможливо змінити на «%s»',
                    Order::STATUSES[$newStatus],
                ));
            }
        } catch (SMException $e) {
            throw new \RuntimeException(sprintf(
                'Неможливо змінити на «%s»',
                Order::STATUSES[$newStatus],
            ), 0, $e);
        }

        $order->setStatus($newStatus);
    }
}
