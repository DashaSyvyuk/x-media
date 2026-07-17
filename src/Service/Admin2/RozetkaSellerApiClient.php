<?php

namespace App\Service\Admin2;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class RozetkaSellerApiClient
{
    private const API_BASE = 'https://api-seller.rozetka.com.ua';
    private const TOKEN_CACHE_KEY = 'rozetka_seller_access_token';
    private const TOKEN_TTL = 82800;
    private const STATUS_MAP_CACHE_KEY = 'rozetka_order_status_labels';
    private const STATUS_MAP_TTL = 3600;
    private const COUNTS_CACHE_KEY = 'rozetka_orders_counts';
    private const COUNTS_TTL = 60;

    /** @var int[] New + in processing + delivering */
    public const ACTIVE_TYPES = [4, 2, 5];

    private const ORDER_EXPAND = 'user,delivery,delivery_service,purchases,status_data,status_available,'
        . 'is_access_change_order,total_quantity,item_details';

    private readonly string $username;
    private readonly string $password;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheItemPoolInterface $cache,
        private readonly LoggerInterface $logger,
        ?string $username = null,
        ?string $password = null,
    ) {
        $this->username = trim((string) ($username ?? ''));
        $this->password = (string) ($password ?? '');
    }

    public function isConfigured(): bool
    {
        return $this->username !== '' && $this->password !== '';
    }

    /**
     * @return array{new: int, inProgress: int}|null
     */
    public function getCounts(): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $cacheItem = $this->cache->getItem(self::COUNTS_CACHE_KEY);
        if ($cacheItem->isHit()) {
            $cached = $cacheItem->get();

            return is_array($cached) ? $cached : null;
        }

        try {
            $response = $this->request('GET', '/orders/counts');
            $content = is_array($response['content'] ?? null) ? $response['content'] : [];
            $normalized = $this->normalizeCounts($content);
            $cacheItem->set($normalized);
            $cacheItem->expiresAfter(self::COUNTS_TTL);
            $this->cache->save($cacheItem);

            return $normalized;
        } catch (\Throwable $e) {
            $this->logger->error('Rozetka counts fetch failed.', ['exception' => $e]);

            return null;
        }
    }

    public function countNewOrders(): int
    {
        $counts = $this->getCounts();

        return $counts['new'] ?? 0;
    }

    /**
     * @param array<string, mixed> $content
     *
     * @return array{new: int, inProgress: int}
     */
    private function normalizeCounts(array $content): array
    {
        $new = $this->extractCount($content, [
            'new', 'New', 'new_orders', 'newOrders', 'status_1', '1',
        ]);
        $inProgress = $this->extractCount($content, [
            'inProgress', 'in_progress', 'processing', 'in_processing', 'status_26', '26',
        ]);

        if (isset($content['statuses']) && is_array($content['statuses'])) {
            $new = max($new, $this->extractCount($content['statuses'], ['1', 1, 'new']));
            $inProgress = max($inProgress, $this->extractCount($content['statuses'], ['26', 26, '2', 2]));
        }

        return [
            'new'        => $new,
            'inProgress' => $inProgress,
        ];
    }

    /**
     * @param array<array-key, mixed> $data
     * @param list<array-key>         $keys
     */
    private function extractCount(array $data, array $keys): int
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data) && is_numeric($data[$key])) {
                return max(0, (int) $data[$key]);
            }
        }

        return 0;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchActiveOrders(int $maxPages = 5, int $pageSize = 50): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        $orders = [];
        $seenIds = [];

        foreach (self::ACTIVE_TYPES as $type) {
            for ($page = 1; $page <= $maxPages; ++$page) {
                try {
                    $response = $this->request('GET', '/orders/search', [
                        'page'   => $page,
                        'sort'   => '-id',
                        'types'  => $type,
                        'limit'  => $pageSize,
                        'expand' => self::ORDER_EXPAND,
                    ]);
                } catch (\Throwable $e) {
                    $this->logger->error('Rozetka orders fetch failed.', [
                        'type'      => $type,
                        'page'      => $page,
                        'exception' => $e,
                    ]);
                    break;
                }

                $batch = $response['content']['orders'] ?? [];
                if (! is_array($batch) || $batch === []) {
                    break;
                }

                foreach ($batch as $order) {
                    if (! is_array($order)) {
                        continue;
                    }

                    $id = (int) ($order['id'] ?? 0);
                    if ($id <= 0 || isset($seenIds[$id])) {
                        continue;
                    }

                    $seenIds[$id] = true;
                    $orders[] = $order;
                }

                $meta = $response['content']['_meta'] ?? $response['content']['meta'] ?? null;
                $pageCount = is_array($meta) ? (int) ($meta['pageCount'] ?? 0) : 0;
                if ($pageCount > 0 && $page >= $pageCount) {
                    break;
                }
            }
        }

        usort($orders, static fn (array $a, array $b): int => ($b['id'] ?? 0) <=> ($a['id'] ?? 0));

        return $orders;
    }

    /**
     * Count marketplace orders placed in the given period (inclusive calendar days).
     */
    public function countOrdersCreatedBetween(\DateTimeInterface $from, \DateTimeInterface $to): int
    {
        if (! $this->isConfigured()) {
            return 0;
        }

        try {
            $response = $this->request('GET', '/orders/search', [
                'page'         => 1,
                'limit'        => 1,
                'types'        => 1,
                'created_from' => $from->format('Y-m-d'),
                'created_to'   => $to->format('Y-m-d'),
                'sort'         => '-id',
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Rozetka orders count fetch failed.', [
                'from'      => $from->format('Y-m-d'),
                'to'        => $to->format('Y-m-d'),
                'exception' => $e,
            ]);

            return 0;
        }

        $content = is_array($response['content'] ?? null) ? $response['content'] : [];
        $meta = $content['_meta'] ?? $content['meta'] ?? null;
        if (is_array($meta) && isset($meta['totalCount'])) {
            return max(0, (int) $meta['totalCount']);
        }

        $orders = $content['orders'] ?? [];

        return is_array($orders) ? count($orders) : 0;
    }

    /**
     * Orders created in period with purchases (for stats / top products).
     *
     * @return list<array<string, mixed>>
     */
    public function fetchOrdersCreatedBetween(
        \DateTimeInterface $from,
        \DateTimeInterface $to,
        int $maxPages = 15,
        int $pageSize = 100,
    ): array {
        if (! $this->isConfigured()) {
            return [];
        }

        $orders = [];
        $seenIds = [];

        for ($page = 1; $page <= $maxPages; ++$page) {
            try {
                $response = $this->request('GET', '/orders/search', [
                    'page'         => $page,
                    'limit'        => $pageSize,
                    'types'        => 1,
                    'created_from' => $from->format('Y-m-d'),
                    'created_to'   => $to->format('Y-m-d'),
                    'sort'         => '-id',
                    'expand'       => 'purchases,item_details,status_data,seller_comment',
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Rozetka orders-for-stats fetch failed.', [
                    'page'      => $page,
                    'from'      => $from->format('Y-m-d'),
                    'to'        => $to->format('Y-m-d'),
                    'exception' => $e,
                ]);
                break;
            }

            $batch = $response['content']['orders'] ?? [];
            if (! is_array($batch) || $batch === []) {
                break;
            }

            foreach ($batch as $order) {
                if (! is_array($order)) {
                    continue;
                }

                $id = (int) ($order['id'] ?? 0);
                if ($id <= 0 || isset($seenIds[$id])) {
                    continue;
                }

                $seenIds[$id] = true;
                $orders[] = $order;
            }

            $meta = $response['content']['_meta'] ?? $response['content']['meta'] ?? null;
            $pageCount = is_array($meta) ? (int) ($meta['pageCount'] ?? 0) : 0;
            if ($pageCount > 0 && $page >= $pageCount) {
                break;
            }
        }

        return $orders;
    }

    /**
     * Lightweight markers for stats charts (created date + status id).
     *
     * @return list<array{created: string, status: int}>
     */
    public function fetchOrderMarkersCreatedBetween(
        \DateTimeInterface $from,
        \DateTimeInterface $to,
        int $maxPages = 15,
        int $pageSize = 100,
    ): array {
        $markers = [];
        foreach ($this->fetchOrdersCreatedBetween($from, $to, $maxPages, $pageSize) as $order) {
            $markers[] = [
                'created' => (string) ($order['created'] ?? ''),
                'status'  => (int) ($order['status'] ?? 0),
            ];
        }

        return $markers;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function fetchOrderDetails(int $rozetkaOrderId): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $response = $this->request('GET', '/orders/' . $rozetkaOrderId, [
                'expand' => self::ORDER_EXPAND . ',chatUser,payment_type_name',
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Rozetka order details fetch failed.', [
                'rozetkaOrderId' => $rozetkaOrderId,
                'exception'      => $e,
            ]);

            return null;
        }

        $content = $response['content'] ?? null;

        return is_array($content) ? $content : null;
    }

    /**
     * @param array{status?: int, ttn?: string, seller_comment?: string} $data
     *
     * @return array<string, mixed>
     */
    public function updateOrder(int $rozetkaOrderId, array $data): array
    {
        $payload = array_filter([
            'status'          => isset($data['status']) ? (int) $data['status'] : null,
            'ttn'             => isset($data['ttn']) ? (string) $data['ttn'] : null,
            'seller_comment'  => isset($data['seller_comment']) ? (string) $data['seller_comment'] : null,
        ], static fn ($value): bool => $value !== null && $value !== '');

        $response = $this->request(
            'PUT',
            '/orders/' . $rozetkaOrderId,
            ['expand' => self::ORDER_EXPAND],
            $payload,
        );

        $content = $response['content'] ?? [];
        if (! is_array($content)) {
            throw new \RuntimeException('Rozetka API returned empty order after update.');
        }

        return $content;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchOrderStatuses(): array
    {
        return $this->fetchAllOrderStatuses();
    }

    /**
     * @return array<int, string>
     */
    public function getStatusLabelMap(): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        $cacheItem = $this->cache->getItem(self::STATUS_MAP_CACHE_KEY);
        if ($cacheItem->isHit()) {
            $map = $cacheItem->get();

            return is_array($map) ? $map : [];
        }

        $map = [];
        foreach ($this->fetchAllOrderStatuses() as $status) {
            $id = (int) ($status['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $label = $this->extractStatusLabel($status);
            if ($label === '' || str_starts_with($label, 'Статус #')) {
                continue;
            }

            $map[$id] = $label;
        }

        if ($map !== []) {
            $cacheItem->set($map);
            $cacheItem->expiresAfter(self::STATUS_MAP_TTL);
            $this->cache->save($cacheItem);
        }

        return $map;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchAllOrderStatuses(): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        $statuses = [];

        for ($page = 1; $page <= 10; ++$page) {
            try {
                $response = $this->request('GET', '/order-statuses/search', [
                    'page'    => $page,
                    'perPage' => 200,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Rozetka order statuses fetch failed.', ['exception' => $e]);
                break;
            }

            $batch = $response['content']['orderStatuses']
                ?? $response['content']['orderStatus']
                ?? $response['content']['statuses']
                ?? [];
            if (! is_array($batch) || $batch === []) {
                break;
            }

            foreach ($batch as $status) {
                if (is_array($status)) {
                    $statuses[] = $status;
                }
            }

            $meta = $response['content']['_meta'] ?? null;
            $pageCount = is_array($meta) ? (int) ($meta['pageCount'] ?? 0) : 0;
            if ($pageCount > 0 && $page >= $pageCount) {
                break;
            }
        }

        return $statuses;
    }

    /**
     * @param array<string, mixed> $status
     */
    private function extractStatusLabel(array $status): string
    {
        foreach (['name_uk', 'name_ua', 'title', 'name', 'name_en'] as $key) {
            if (is_string($status[$key] ?? null) && trim($status[$key]) !== '') {
                return trim($status[$key]);
            }
        }

        $id = (int) ($status['id'] ?? 0);

        return $id > 0 ? ('Статус #' . $id) : '—';
    }

    /**
     * @param array<string, scalar|null> $query
     * @param array<string, mixed>       $json
     *
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $query = [], array $json = []): array
    {
        $token = $this->getAccessToken();
        $options = [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Accept'        => 'application/json',
            ],
        ];

        if ($query !== []) {
            $options['query'] = $query;
        }

        if ($json !== []) {
            $options['headers']['Content-Type'] = 'application/json';
            $options['json'] = $json;
        }

        $response = $this->httpClient->request($method, self::API_BASE . $path, $options);
        $statusCode = $response->getStatusCode();
        $data = $response->toArray(false);

        if ($statusCode >= 400 || ! ($data['success'] ?? false)) {
            $errors = $data['errors'] ?? [];
            $message = is_array($errors) && is_string($errors['message'] ?? null)
                ? $errors['message']
                : (is_string($data['message'] ?? null) ? $data['message'] : 'Rozetka API error');
            throw new \RuntimeException(sprintf('%s (HTTP %d)', $message, $statusCode));
        }

        return $data;
    }

    private function getAccessToken(): string
    {
        $cacheItem = $this->cache->getItem(self::TOKEN_CACHE_KEY);
        if ($cacheItem->isHit()) {
            $token = (string) $cacheItem->get();
            if ($token !== '') {
                return $token;
            }
        }

        $response = $this->httpClient->request('POST', self::API_BASE . '/sites', [
            'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
            'json'    => [
                'username' => $this->username,
                'password' => base64_encode($this->password),
            ],
        ]);

        $data = $response->toArray(false);
        if (! ($data['success'] ?? false)) {
            $message = is_string($data['message'] ?? null) ? $data['message'] : 'Rozetka auth failed';
            throw new \RuntimeException($message);
        }

        $token = (string) ($data['content']['access_token'] ?? '');
        if ($token === '') {
            throw new \RuntimeException('Rozetka auth returned empty token.');
        }

        $cacheItem->set($token);
        $cacheItem->expiresAfter(self::TOKEN_TTL);
        $this->cache->save($cacheItem);

        return $token;
    }
}
