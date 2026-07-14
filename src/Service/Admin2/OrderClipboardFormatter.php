<?php

namespace App\Service\Admin2;

use App\Entity\Order;
use App\Entity\VendorOrder;

final class OrderClipboardFormatter
{
    public function formatLocalOrder(Order $order): string
    {
        $items = $this->formatLocalItems($order);

        return $this->build(
            $items,
            $order->getPhone(),
            trim(sprintf('%s %s', $order->getSurname() ?? '', $order->getName())),
            $this->resolveLocalAddress($order),
            ! $order->getPaymentStatus(),
            $order->getTotal(),
            false,
        );
    }

    public function formatVendorOrder(VendorOrder $order): string
    {
        $supplier = $order->getSupplier();
        $lines = [
            trim($supplier->getTitle()),
            trim((string) ($supplier->getAddress() ?? '')),
            sprintf('%s - %d zl', trim($order->getSupplierOrderNumber()), $order->getPrice()),
        ];

        foreach ($order->getItems() as $item) {
            $title = trim($item->getTitle());
            if ($title === '') {
                continue;
            }

            $quantity = max(1, $item->getQuantity());
            $lines[] = $quantity > 1 ? sprintf('%d× %s', $quantity, $title) : $title;
        }

        if (count($lines) === 3 && trim($order->getProductTitle()) !== '') {
            $lines[] = trim($order->getProductTitle());
        }

        return implode("\n", $lines);
    }

    /**
     * @param array<string, mixed> $apiOrder
     */
    public function formatRozetkaOrder(array $apiOrder): string
    {
        $delivery = is_array($apiOrder['delivery'] ?? null) ? $apiOrder['delivery'] : [];

        return $this->build(
            $this->formatRozetkaItems($apiOrder),
            $this->resolveRozetkaPhone($delivery, $apiOrder),
            $this->asString($delivery['recipient_title'] ?? null),
            $this->formatRozetkaAddress($delivery),
            $this->isRozetkaCod($apiOrder),
            (int) round((float) ($apiOrder['cost_with_discount'] ?? $apiOrder['cost'] ?? 0)),
            true,
        );
    }

    /**
     * @return string[]
     */
    private function formatLocalItems(Order $order): array
    {
        $items = [];
        foreach ($order->getItems() as $item) {
            $name = (string) $item->getProduct()->getTitle();
            $qty = $item->getCount();
            $items[] = $qty > 1 ? sprintf('%d× %s', $qty, $name) : $name;
        }

        return $items;
    }

    /**
     * @param array<string, mixed> $apiOrder
     *
     * @return string[]
     */
    private function formatRozetkaItems(array $apiOrder): array
    {
        $items = [];
        foreach ($apiOrder['purchases'] ?? [] as $purchase) {
            if (! is_array($purchase)) {
                continue;
            }

            $name = trim((string) ($purchase['item_name'] ?? $purchase['name'] ?? '—'));
            $qty = max(1, (int) ($purchase['quantity'] ?? $purchase['count'] ?? 1));
            $items[] = $qty > 1 ? sprintf('%d× %s', $qty, $name) : $name;
        }

        return $items;
    }

    private function resolveLocalAddress(Order $order): string
    {
        $address = trim((string) ($order->getAddress() ?? ''));
        if ($address !== '') {
            return $address;
        }

        $parts = [];
        $city = $order->getNovaPoshtaCity();
        if ($city !== null) {
            $parts[] = $city->getTitle();
        }

        $office = $order->getNovaPoshtaOffice();
        if ($office !== null) {
            $parts[] = $office->getTitle();
        }

        return implode(', ', $parts);
    }

    /**
     * @param array<string, mixed> $delivery
     * @param array<string, mixed> $apiOrder
     */
    private function resolveRozetkaPhone(array $delivery, array $apiOrder): string
    {
        foreach ([
            $delivery['recipient_phone'] ?? null,
            $delivery['phone'] ?? null,
            $apiOrder['user_phone'] ?? null,
        ] as $value) {
            $phone = $this->asString($value);
            if ($phone !== '') {
                return $this->formatRozetkaPhone($phone);
            }
        }

        return '';
    }

    private function formatRozetkaPhone(string $phone): string
    {
        $phone = trim($phone);
        if ($phone === '') {
            return '';
        }

        return str_starts_with($phone, '+') ? $phone : '+' . $phone;
    }

    /**
     * @param array<string, mixed> $delivery
     */
    private function formatRozetkaAddress(array $delivery): string
    {
        $parts = array_values(array_unique(array_filter([
            $this->asString($delivery['city_title'] ?? null),
            $this->asString($delivery['city'] ?? null),
            $this->asString($delivery['place_title'] ?? null),
            $this->asString($delivery['place'] ?? null),
            $this->asString($delivery['place_street'] ?? $delivery['street'] ?? null),
            $this->asString($delivery['place_house'] ?? null),
            $this->asString($delivery['place_flat'] ?? null),
            $this->asString($delivery['recipient_warehouse'] ?? $delivery['warehouse'] ?? null),
        ], static fn (string $part): bool => $part !== '')));

        return implode(', ', $parts);
    }

    /**
     * @param array<string, mixed> $apiOrder
     */
    private function isRozetkaCod(array $apiOrder): bool
    {
        if ($this->isRozetkaPrepaid($apiOrder)) {
            return false;
        }

        $type = strtolower($this->asString($apiOrder['payment_type'] ?? null));
        if (in_array($type, ['cash', 'cod'], true)) {
            return true;
        }

        $name = mb_strtolower($this->asString($apiOrder['payment_type_name'] ?? null));
        if ($name === '') {
            return false;
        }

        if (str_contains($name, 'готів') || str_contains($name, 'налож') || str_contains($name, 'отриман')) {
            return true;
        }

        return false;
    }

    /**
     * @param array<string, mixed> $apiOrder
     */
    private function isRozetkaPrepaid(array $apiOrder): bool
    {
        $paymentStatus = $apiOrder['payment_status'] ?? $apiOrder['status_payment'] ?? null;
        if ($paymentStatus === true || $paymentStatus === 1 || $paymentStatus === '1') {
            return true;
        }

        if (is_string($paymentStatus) && in_array(strtolower($paymentStatus), ['paid', 'success', 'completed'], true)) {
            return true;
        }

        $name = mb_strtolower($this->asString($apiOrder['payment_type_name'] ?? null));
        if ($name !== '' && (
            str_contains($name, 'рахунок')
            || str_contains($name, 'карт')
            || str_contains($name, 'онлайн')
            || str_contains($name, 'передоплат')
            || str_contains($name, 'безгот')
        )) {
            return true;
        }

        $type = strtolower($this->asString($apiOrder['payment_type'] ?? null));

        return in_array($type, ['card', 'cashless', 'online', 'bank'], true);
    }

    /**
     * @param string[] $productLines
     */
    private function build(
        array $productLines,
        string $phone,
        string $recipientName,
        string $address,
        bool $isCod,
        int $total,
        bool $isRozetkaSource,
    ): string {
        $lines = $productLines;
        $lines[] = 'Отримувач:';

        if ($phone !== '') {
            $lines[] = $phone;
        }

        if ($recipientName !== '') {
            $lines[] = $recipientName;
        }

        if ($address !== '') {
            $lines[] = $address;
        }

        $lines[] = $isCod
            ? sprintf('Наложка - %s', $this->formatPrice($total))
            : 'Без наложки';

        $checkLine = 'Чек ' . ($isRozetkaSource ? 'розетка' : 'наш');
        if (! $isCod) {
            $checkLine .= ' - ' . $this->formatPrice($total);
        }

        $lines[] = $checkLine;

        return implode("\n", $lines);
    }

    private function formatPrice(int $amount): string
    {
        return number_format($amount, 0, '.', ' ') . ' грн';
    }

    private function asString(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_string($value)) {
            return trim($value);
        }

        if (is_int($value) || is_float($value)) {
            return trim((string) $value);
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (! is_array($value)) {
            return '';
        }

        foreach (['title', 'name_uk', 'name_ua', 'name', 'label', 'value', 'text'] as $key) {
            if (! array_key_exists($key, $value)) {
                continue;
            }

            $string = $this->asString($value[$key]);
            if ($string !== '') {
                return $string;
            }
        }

        return '';
    }
}
