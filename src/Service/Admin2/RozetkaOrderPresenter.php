<?php

namespace App\Service\Admin2;

use App\Entity\Order;
use App\Repository\ProductRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class RozetkaOrderPresenter
{
    /**
     * Preferred seller workflow labels. Used as the primary source of Ukrainian titles.
     *
     * @var array<int, string>
     */
    private const ALLOWED_STATUS_OPTIONS = [
        1  => 'Нове',
        26 => 'Обробляється менеджером',
        2  => 'Комплектується. Дані підтверджені',
        61 => 'Заплановано передачу перевізникові',
    ];

    /** Lower = higher in the Rozetka orders list. */
    private const STATUS_SORT_ORDER = [
        1  => 0,  // Нове
        26 => 1,  // Обробляється менеджером
        2  => 2,  // Комплектується
        61 => 3,  // Передано / заплановано перевізнику
        3  => 3,
    ];

    private const STATUS_TONE = [
        1  => 'new',
        26 => 'processing',
        2  => 'packing',
        61 => 'shipping',
        3  => 'shipping',
    ];

    public function __construct(
        private readonly RozetkaSellerApiClient $apiClient,
        private readonly ProductRepository $productRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly RozetkaOrderPaymentResolver $paymentResolver,
    ) {
    }

    /**
     * @param array<string, mixed> $apiOrder
     *
     * @return array<string, mixed>
     */
    public function presentListItem(array $apiOrder): array
    {
        $delivery = is_array($apiOrder['delivery'] ?? null) ? $apiOrder['delivery'] : [];
        $statusData = is_array($apiOrder['status_data'] ?? null) ? $apiOrder['status_data'] : [];

        $statusId = (int) ($apiOrder['status'] ?? 0);

        return [
            'id'             => (int) ($apiOrder['id'] ?? 0),
            'created'        => (string) ($apiOrder['created'] ?? ''),
            'status'         => $this->asString(
                $statusData['name_uk'] ?? $statusData['name_ua'] ?? $statusData['name'] ?? $statusData['title'] ?? null,
                '—',
            ),
            'statusId'       => $statusId,
            'statusTone'     => self::STATUS_TONE[$statusId] ?? 'default',
            'statusSort'     => self::STATUS_SORT_ORDER[$statusId] ?? 50,
            'total'          => (int) round((float) ($apiOrder['cost_with_discount'] ?? $apiOrder['cost'] ?? 0)),
            'phone'          => $this->asString($apiOrder['user_phone'] ?? null),
            'recipient'      => $this->asString($delivery['recipient_title'] ?? null),
            'ttn'            => $this->asString($apiOrder['ttn'] ?? null),
            'items'          => $this->presentItems($apiOrder),
            'paymentStatus'  => $this->paymentResolver->isPaid($apiOrder),
            'sellerComment'  => $this->asString($apiOrder['current_seller_comment'] ?? null),
        ];
    }

    /**
     * @param array<string, mixed> $apiOrder
     *
     * @return array<string, mixed>
     */
    public function presentDetail(array $apiOrder): array
    {
        $list = $this->presentListItem($apiOrder);
        $delivery = is_array($apiOrder['delivery'] ?? null) ? $apiOrder['delivery'] : [];
        $user = is_array($apiOrder['user'] ?? null) ? $apiOrder['user'] : [];
        $deliveryService = is_array($apiOrder['delivery_service'] ?? null) ? $apiOrder['delivery_service'] : [];

        $list['email'] = $this->asString($user['email'] ?? null);
        $list['comment'] = $this->asString($apiOrder['comment'] ?? null);
        $list['sellerComment'] = $this->asString($apiOrder['current_seller_comment'] ?? null);
        $list['paymentStatus'] = $this->paymentResolver->isPaid($apiOrder);
        $list['address'] = $this->formatAddress($delivery);
        $list['deliveryName'] = $this->asString(
            $deliveryService['name'] ?? $delivery['delivery_service_name'] ?? null,
            '—',
        );
        $list['canEdit'] = $this->canEditOrder($apiOrder);
        $list['statusAvailable'] = $this->presentStatusOptions($apiOrder);

        return $list;
    }

    /**
     * @param array<string, mixed> $apiOrder
     *
     * @return array<string, mixed>
     */
    public function presentBoardItem(array $apiOrder): array
    {
        $detail = $this->presentListItem($apiOrder);

        return [
            'type'            => 'rozetka',
            'id'              => $detail['id'],
            'key'             => 'rozetka:' . $detail['id'],
            'label'           => 'RZ ' . $detail['id'],
            'created'         => substr($detail['created'], 0, 16),
            'status'          => $detail['status'],
            'statusId'        => $detail['statusId'],
            'statusAvailable' => $this->presentStatusOptions($apiOrder),
            'canEditStatus'   => $this->canEditOrder($apiOrder),
            'statusGroup'     => 'Rozetka',
            'total'           => $detail['total'],
            'phone'           => $detail['phone'],
            'recipient'       => $detail['recipient'],
            'items'           => $detail['items'],
            'ttn'             => $detail['ttn'],
            'paymentStatus'   => $detail['paymentStatus'],
            'editUrl'         => null,
            'isRozetka'       => true,
        ];
    }

    /**
     * @param array<string, mixed> $apiOrder
     *
     * @return array<int, array{id: int, label: string}>
     */
    private function presentStatusOptions(array $apiOrder): array
    {
        $currentStatus = (int) ($apiOrder['status'] ?? 0);
        $statusData = is_array($apiOrder['status_data'] ?? null) ? $apiOrder['status_data'] : [];
        $labelMap = $this->apiClient->getStatusLabelMap();
        $optionsById = [];

        $addOption = function (
            int $statusId,
            ?string $rawLabel = null,
        ) use (
            &$optionsById,
            $currentStatus,
            $statusData,
            $labelMap,
        ): void {
            if ($statusId <= 0) {
                return;
            }

            $optionsById[$statusId] = [
                'id'    => $statusId,
                'label' => $this->statusLabel(
                    $statusId,
                    $currentStatus,
                    $statusData,
                    $labelMap,
                    $rawLabel,
                ),
            ];
        };

        // Always seed with known workflow statuses so Ukrainian titles never disappear.
        foreach (self::ALLOWED_STATUS_OPTIONS as $statusId => $_) {
            $addOption($statusId);
        }

        // Only keep the known seller workflow. Extra API ids (often 320/447 without
        // usable names) only pollute the select as "Статус #…".
        if ($currentStatus > 0 && ! isset($optionsById[$currentStatus])) {
            $addOption($currentStatus);
        }

        return array_values(array_filter(
            $optionsById,
            static fn (array $option): bool => ! str_starts_with($option['label'], 'Статус #'),
        ));
    }

    /**
     * @param array<string, mixed> $statusData
     * @param array<int, string>   $labelMap
     */
    private function statusLabel(
        int $statusId,
        int $currentStatus,
        array $statusData,
        array $labelMap,
        ?string $rawLabel = null,
    ): string {
        if ($statusId === $currentStatus) {
            $currentLabel = $this->asString(
                $statusData['name_uk'] ?? $statusData['name_ua'] ?? $statusData['name'] ?? $statusData['title'] ?? null,
                '',
            );
            if ($currentLabel !== '') {
                return $currentLabel;
            }
        }

        if (isset(self::ALLOWED_STATUS_OPTIONS[$statusId])) {
            return self::ALLOWED_STATUS_OPTIONS[$statusId];
        }

        if (
            isset($labelMap[$statusId])
            && trim($labelMap[$statusId]) !== ''
            && ! str_starts_with($labelMap[$statusId], 'Статус #')
        ) {
            return $labelMap[$statusId];
        }

        if ($rawLabel !== null && trim($rawLabel) !== '' && ! str_starts_with(trim($rawLabel), 'Статус #')) {
            return trim($rawLabel);
        }

        return 'Статус #' . $statusId;
    }

    /**
     * @param array<string, mixed> $apiOrder
     */
    private function canEditOrder(array $apiOrder): bool
    {
        if (array_key_exists('is_access_change_order', $apiOrder)) {
            if ((bool) $apiOrder['is_access_change_order']) {
                return true;
            }
        }

        // New Rozetka orders often need a first status change even when edit-flag is false.
        $available = $apiOrder['status_available'] ?? null;
        if (is_array($available) && $available !== []) {
            return true;
        }

        $currentStatus = (int) ($apiOrder['status'] ?? 0);

        return $currentStatus === 1 || isset(self::ALLOWED_STATUS_OPTIONS[$currentStatus]);
    }

    /**
     * @param array<string, mixed> $apiOrder
     *
     * @return array<int, array{name: string, qty: int, price: int, productId: ?int, productUrl: ?string}>
     */
    private function presentItems(array $apiOrder): array
    {
        $items = [];
        foreach ($apiOrder['purchases'] ?? [] as $purchase) {
            if (! is_array($purchase)) {
                continue;
            }

            $productId = $this->resolveLocalProductId($purchase);

            $items[] = [
                'name'       => $this->asString($purchase['item_name'] ?? $purchase['name'] ?? null, '—'),
                'qty'        => max(1, (int) ($purchase['quantity'] ?? $purchase['count'] ?? 1)),
                'price'      => (int) round((float) ($purchase['price'] ?? $purchase['cost'] ?? 0)),
                'productId'  => $productId,
                'productUrl' => $this->buildProductUrl($productId),
            ];
        }

        return $items;
    }

    /**
     * Resolve our local product id from a Rozetka purchase line.
     *
     * @param array<string, mixed> $purchase
     */
    public function resolveLocalProductId(array $purchase): ?int
    {
        $candidates = [];

        foreach (['item', 'item_details'] as $nestedKey) {
            $nested = $purchase[$nestedKey] ?? null;
            if (! is_array($nested)) {
                continue;
            }

            $candidates[] = $nested['price_offer_id'] ?? null;
            $candidates[] = $nested['article'] ?? null;
            $candidates[] = $nested['offer_id'] ?? null;
            $candidates[] = $nested['id'] ?? null;
        }

        $candidates[] = $purchase['price_offer_id'] ?? null;
        $candidates[] = $purchase['article'] ?? null;
        $candidates[] = $purchase['offer_id'] ?? null;
        $candidates[] = $purchase['item_id'] ?? null;

        foreach ($candidates as $candidate) {
            $productId = $this->normalizeProductId($candidate);
            if ($productId !== null) {
                return $productId;
            }
        }

        return null;
    }

    private function normalizeProductId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        if (is_string($value)) {
            $value = trim($value);
            if ($value !== '' && ctype_digit($value)) {
                $id = (int) $value;

                return $id > 0 ? $id : null;
            }
        }

        return null;
    }

    private function buildProductUrl(?int $productId): ?string
    {
        if ($productId === null || $this->productRepository->find($productId) === null) {
            return null;
        }

        return $this->urlGenerator->generate(
            'product',
            ['id' => $productId],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );
    }

    /**
     * @param array<string, mixed> $delivery
     */
    private function formatAddress(array $delivery): string
    {
        $isPickup = (int) ($delivery['delivery_method_id'] ?? 0) !== 2;
        $warehouseNumber = $this->asString(
            $delivery['place_number']
            ?? $delivery['warehouse_number']
            ?? $delivery['recipient_warehouse']
            ?? $delivery['warehouse']
            ?? null,
        );

        $warehouseLabel = '';
        if ($warehouseNumber !== '' && $isPickup) {
            $warehouseLabel = preg_match('/^\d+$/u', $warehouseNumber) === 1
                ? 'Відділення №' . $warehouseNumber
                : $warehouseNumber;
        }

        $parts = array_values(array_unique(array_filter([
            $this->asString($delivery['city_title'] ?? null),
            $this->asString($delivery['city'] ?? null),
            $warehouseLabel,
            $this->asString($delivery['place_title'] ?? null),
            $this->asString($delivery['place'] ?? null),
            $this->asString($delivery['place_street'] ?? $delivery['street'] ?? null),
            $this->asString($delivery['place_house'] ?? null),
            $this->asString($delivery['place_flat'] ?? null),
        ], static fn (string $part): bool => $part !== '')));

        return implode(', ', $parts);
    }

    private function asString(mixed $value, string $default = ''): string
    {
        if ($value === null) {
            return $default;
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
            return $default;
        }

        foreach (['title', 'name_uk', 'name_ua', 'name', 'label', 'value', 'text', 'full_name'] as $key) {
            if (! array_key_exists($key, $value)) {
                continue;
            }

            $string = $this->asString($value[$key], '');
            if ($string !== '') {
                return $string;
            }
        }

        return $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function presentLocalOrder(Order $order): array
    {
        $items = [];
        foreach ($order->getItems() as $item) {
            $items[] = [
                'name'  => (string) $item->getProduct()->getTitle(),
                'qty'   => $item->getCount(),
                'price' => (int) ($item->getPrice() ?? 0),
            ];
        }

        $grouped = Order::GROUPED_STATUSES[$order->getStatus()] ?? null;

        return [
            'type'           => 'local',
            'id'             => $order->getId() ?? 0,
            'key'            => 'order:' . ($order->getId() ?? 0),
            'label'          => $order->getOrderNumber(),
            'created'        => $order->getCreatedAt()->format('d.m.Y H:i'),
            'status'         => Order::STATUSES[$order->getStatus()] ?? $order->getStatus(),
            'statusGroup'    => $grouped['title'] ?? '',
            'total'          => $order->getTotal(),
            'phone'          => $order->getPhone(),
            'recipient'      => trim(sprintf('%s %s', $order->getName(), $order->getSurname() ?? '')),
            'items'          => $items,
            'ttn'            => (string) ($order->getTtn() ?? ''),
            'paymentStatus'  => $order->getPaymentStatus(),
            'editUrl'        => null,
            'isRozetka'      => in_array(Order::LABEL_ROZETKA, $order->getLabels() ?? [], true),
        ];
    }
}
