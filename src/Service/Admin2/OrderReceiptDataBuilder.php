<?php

namespace App\Service\Admin2;

use App\Entity\Order;
use App\Repository\OrderRepository;
use NumberFormatter;

final class OrderReceiptDataBuilder
{
    private const DEFAULT_WARRANTY = 24;

    /** @var array<int, string> */
    private const MONTHS_GENITIVE = [
        1  => 'січня',
        2  => 'лютого',
        3  => 'березня',
        4  => 'квітня',
        5  => 'травня',
        6  => 'червня',
        7  => 'липня',
        8  => 'серпня',
        9  => 'вересня',
        10 => 'жовтня',
        11 => 'листопада',
        12 => 'грудня',
    ];

    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly RozetkaSellerApiClient $rozetkaApiClient,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function buildForLocal(int $orderId): array
    {
        $order = $this->orderRepository->find($orderId);
        if (! $order instanceof Order) {
            throw new \RuntimeException('Замовлення не знайдено.');
        }

        $items = [];
        $index = 1;
        foreach ($order->getItems() as $item) {
            $price = (int) ($item->getPrice() ?? 0);
            $qty = $item->getCount();
            $items[] = [
                'no'       => $index++,
                'name'     => (string) $item->getProduct()->getTitle(),
                'warranty' => self::DEFAULT_WARRANTY,
                'qty'      => $qty,
                'price'    => $price,
                'sum'      => $price * $qty,
            ];
        }

        $total = $order->getTotal();
        $date = $order->getCreatedAt();

        return $this->buildPayload(
            template: 'xmedia',
            filename: 'x-media_' . $this->sanitizeFilenamePart($order->getOrderNumber()),
            checkNumber: $order->getOrderNumber(),
            date: $date,
            items: $items,
            total: $total,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function buildForRozetka(int $rozetkaOrderId): array
    {
        if (! $this->rozetkaApiClient->isConfigured()) {
            throw new \RuntimeException('Rozetka API не налаштовано.');
        }

        $apiOrder = $this->rozetkaApiClient->fetchOrderDetails($rozetkaOrderId);
        if ($apiOrder === null) {
            throw new \RuntimeException('Замовлення Rozetka не знайдено.');
        }

        $items = [];
        $index = 1;
        foreach ($apiOrder['purchases'] ?? [] as $purchase) {
            if (! is_array($purchase)) {
                continue;
            }

            $price = (int) round((float) ($purchase['price'] ?? $purchase['cost'] ?? 0));
            $qty = max(1, (int) ($purchase['quantity'] ?? $purchase['count'] ?? 1));
            $items[] = [
                'no'       => $index++,
                'name'     => trim((string) ($purchase['item_name'] ?? $purchase['name'] ?? '—')),
                'warranty' => self::DEFAULT_WARRANTY,
                'qty'      => $qty,
                'price'    => $price,
                'sum'      => $price * $qty,
            ];
        }

        $total = (int) round((float) ($apiOrder['cost_with_discount'] ?? $apiOrder['cost'] ?? 0));
        $created = (string) ($apiOrder['created'] ?? 'now');

        try {
            $date = new \DateTimeImmutable($created);
        } catch (\Throwable) {
            $date = new \DateTimeImmutable();
        }

        return $this->buildPayload(
            template: 'rozetka',
            filename: 'rozetka_' . $rozetkaOrderId,
            checkNumber: (string) $rozetkaOrderId,
            date: $date,
            items: $items,
            total: $total,
        );
    }

    public function formatAmountWords(int $amount): string
    {
        $formatter = new NumberFormatter('uk_UA', NumberFormatter::SPELLOUT);
        $words = $formatter->format($amount);
        if (! is_string($words) || $words === '') {
            return (string) $amount;
        }

        return mb_strtoupper(mb_substr($words, 0, 1)) . mb_substr($words, 1) . ' гривень';
    }

    public function formatMoney(int $amount): string
    {
        return number_format($amount, 0, '.', ' ');
    }

    /**
     * @param array<int, array{no:int,name:string,warranty:int,qty:int,price:int,sum:int}> $items
     *
     * @return array<string, mixed>
     */
    private function buildPayload(
        string $template,
        string $filename,
        string $checkNumber,
        \DateTimeInterface $date,
        array $items,
        int $total,
    ): array {
        if ($items === []) {
            $items[] = [
                'no'       => 1,
                'name'     => '—',
                'warranty' => self::DEFAULT_WARRANTY,
                'qty'      => 1,
                'price'    => 0,
                'sum'      => 0,
            ];
        }

        if ($total <= 0) {
            $total = array_sum(array_column($items, 'sum'));
        }

        return [
            'template'    => $template,
            'filename'    => $filename,
            'checkNumber' => $checkNumber,
            'dateDay'     => $date->format('d'),
            'dateMonth'   => ' ' . self::MONTHS_GENITIVE[(int) $date->format('n')] . ' ',
            'dateYear'    => $date->format('Y'),
            'items'       => $items,
            'total'       => $total,
            'totalWords'  => $this->formatAmountWords($total),
        ];
    }

    private function sanitizeFilenamePart(string $value): string
    {
        $value = preg_replace('/[^\w\-]+/u', '_', $value) ?? $value;

        return trim($value, '_') !== '' ? trim($value, '_') : 'order';
    }
}
