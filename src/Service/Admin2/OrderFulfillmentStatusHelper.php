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
            61, 62, 63, 64, 65, 66, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12 => 'shipping',
            default => 'default',
        };
    }

    public function isGreenTone(string $tone): bool
    {
        return in_array($tone, ['new', 'packing'], true);
    }

    public function isPackingTone(string $tone): bool
    {
        return $tone === 'packing';
    }

    public function isNewTone(string $tone): bool
    {
        return $tone === 'new';
    }

    public function isProcessingTone(string $tone): bool
    {
        return $tone === 'processing';
    }

    public function isShippingTone(string $tone): bool
    {
        return $tone === 'shipping';
    }

    public function isLocalCourierDelivering(string $status): bool
    {
        return $status === Order::COURIER_DELIVERING;
    }

    public function isLocalNovaPoshtaDelivering(string $status): bool
    {
        return $status === Order::NOVA_POSHTA_DELIVERING;
    }

    /**
     * Rozetka order already in delivery / with carrier — hide from management board.
     */
    public function isRozetkaDelivering(int $statusId, string $ttn = ''): bool
    {
        if ($this->isShippingTone($this->toneForRozetka($statusId))) {
            return true;
        }

        if (in_array($statusId, [3, 61, 62, 63, 64, 65, 66], true)) {
            return true;
        }

        return $this->isRozetkaCarrierTransfer($statusId, $ttn);
    }

    /**
     * Rozetka handed to carrier — show on NP column when a waybill exists.
     */
    public function isRozetkaCarrierTransfer(int $statusId, string $ttn): bool
    {
        if (trim($ttn) === '') {
            return false;
        }

        // 61 — заплановано/передано перевізнику; 3 — передано службі доставки.
        if (in_array($statusId, [61, 3, 62, 63, 64, 65, 66], true)) {
            return true;
        }

        return $this->isShippingTone($this->toneForRozetka($statusId));
    }
}
