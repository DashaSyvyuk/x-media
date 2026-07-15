<?php

namespace App\Service\Admin2;

use App\Entity\Order;
use App\Repository\ProductRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class RozetkaOrderPresenter
{
    /** @var array<int, string> */
    private const ALLOWED_STATUS_OPTIONS = [
        1  => 'Нове',
        26 => 'Обробляється менеджером',
        2  => 'Комплектується. Дані підтверджені',
        61 => 'Заплановано передачу перевізникові',
    ];

    public function __construct(
        private readonly RozetkaSellerApiClient $apiClient,
        private readonly ProductRepository $productRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
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

        return [
            'id'        => (int) ($apiOrder['id'] ?? 0),
            'created'   => (string) ($apiOrder['created'] ?? ''),
            'status'    => $this->asString(
                $statusData['name_uk'] ?? $statusData['name_ua'] ?? $statusData['name'] ?? $statusData['title'] ?? null,
                '—',
            ),
            'statusId'  => (int) ($apiOrder['status'] ?? 0),
            'total'     => (int) round((float) ($apiOrder['cost_with_discount'] ?? $apiOrder['cost'] ?? 0)),
            'phone'     => $this->asString($apiOrder['user_phone'] ?? null),
            'recipient' => $this->asString($delivery['recipient_title'] ?? null),
            'ttn'       => $this->asString($apiOrder['ttn'] ?? null),
            'items'     => $this->presentItems($apiOrder),
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
        $list['address'] = $this->formatAddress($delivery);
        $list['deliveryName'] = $this->asString(
            $deliveryService['name'] ?? $delivery['delivery_service_name'] ?? null,
            '—',
        );
        $list['canEdit'] = (bool) ($apiOrder['is_access_change_order'] ?? true);
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
            'canEditStatus'   => (bool) ($apiOrder['is_access_change_order'] ?? true),
            'statusGroup'     => 'Rozetka',
            'total'           => $detail['total'],
            'phone'           => $detail['phone'],
            'recipient'       => $detail['recipient'],
            'items'           => $detail['items'],
            'ttn'             => $detail['ttn'],
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

        $options = [];
        foreach (self::ALLOWED_STATUS_OPTIONS as $statusId => $defaultLabel) {
            $options[] = [
                'id'    => $statusId,
                'label' => $this->resolveStatusLabel($statusId, $statusData, $currentStatus, $labelMap, $defaultLabel),
            ];
        }

        return $options;
    }

    /**
     * @param array<string, mixed> $statusData
     * @param array<int, string>     $labelMap
     */
    private function resolveStatusLabel(
        int $statusId,
        array $statusData,
        int $currentStatus,
        array $labelMap,
        ?string $defaultLabel = null,
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

        if ($defaultLabel !== null && $defaultLabel !== '') {
            return $defaultLabel;
        }

        if (isset($labelMap[$statusId]) && $labelMap[$statusId] !== '') {
            return $labelMap[$statusId];
        }

        return 'Статус #' . $statusId;
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
     * @param array<string, mixed> $purchase
     */
    private function resolveLocalProductId(array $purchase): ?int
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
        }

        $candidates[] = $purchase['price_offer_id'] ?? null;
        $candidates[] = $purchase['article'] ?? null;
        $candidates[] = $purchase['offer_id'] ?? null;

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
            'type'        => 'local',
            'id'          => $order->getId() ?? 0,
            'key'         => 'order:' . ($order->getId() ?? 0),
            'label'       => $order->getOrderNumber(),
            'created'     => $order->getCreatedAt()->format('d.m.Y H:i'),
            'status'      => Order::STATUSES[$order->getStatus()] ?? $order->getStatus(),
            'statusGroup' => $grouped['title'] ?? '',
            'total'       => $order->getTotal(),
            'phone'       => $order->getPhone(),
            'recipient'   => trim(sprintf('%s %s', $order->getName(), $order->getSurname() ?? '')),
            'items'       => $items,
            'ttn'         => (string) ($order->getTtn() ?? ''),
            'editUrl'     => null,
            'isRozetka'   => in_array(Order::LABEL_ROZETKA, $order->getLabels() ?? [], true),
        ];
    }
}
