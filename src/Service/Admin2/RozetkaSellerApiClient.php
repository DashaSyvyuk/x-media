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

    /** @var int[] New + in processing */
    public const ACTIVE_TYPES = [4, 2];

    private const ORDER_EXPAND = 'user,delivery,delivery_service,purchases,status_data,status_available,'
        . 'is_access_change_order,total_quantity,item_details';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheItemPoolInterface $cache,
        private readonly LoggerInterface $logger,
        private readonly string $username,
        private readonly string $password,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->username !== '' && $this->password !== '';
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getCounts(): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $response = $this->request('GET', '/orders/counts');

            return is_array($response['content'] ?? null) ? $response['content'] : null;
        } catch (\Throwable $e) {
            $this->logger->error('Rozetka counts fetch failed.', ['exception' => $e]);

            return null;
        }
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

            $map[$id] = $this->extractStatusLabel($status);
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
