<?php

namespace App\Service\Admin2;

use App\Entity\Order;

final class OrderFulfillmentStatusHelper
{
    public const ROZETKA_STATUS_CONFIRMED = 2;

    public function localStatusAfterVendorDelivered(): string
    {
        return Order::PACKING;
    }

    public function rozetkaStatusAfterVendorDelivered(): int
    {
        return self::ROZETKA_STATUS_CONFIRMED;
    }

    public function toneForLocal(string $status): string
    {
        return match ($status) {
            Order::NEW, Order::NOT_APPROVED => 'new',
            Order::APPROVED => 'processing',
            Order::PACKING => 'packing',
            Order::NOVA_POSHTA_DELIVERING, Order::COURIER_DELIVERING => 'shipping',
            Order::COMPLETED => 'done',
            Order::CANCELED_NOT_CONFIRMED,
            Order::CANCELED_NO_PRODUCT,
            Order::CANCELED_NOT_PICKED_UP => 'canceled',
            default => 'default',
        };
    }

    public function toneForRozetka(int $statusId): string
    {
        return match ($statusId) {
            1 => 'new',
            26 => 'processing',
            self::ROZETKA_STATUS_CONFIRMED => 'packing',
            61 => 'shipping',
            default => 'default',
        };
    }

    public function isGreenTone(string $tone): bool
    {
        return in_array($tone, ['new', 'packing'], true);
    }
}
