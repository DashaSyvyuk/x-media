<?php

namespace App\Service\Admin2;

use App\Entity\Product;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class XKomPriceFetcher
{
    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 '
        . '(KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
    private const CACHE_TTL_SECONDS = 3 * 60 * 60;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheItemPoolInterface $cache,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param Product[] $products
     *
     * @return array<int, XKomProductInfo|null> product id => info or null on fetch error
     */
    public function fetchForProducts(array $products): array
    {
        $result = [];
        foreach ($products as $product) {
            $url = trim((string) ($product->getXkomUrl() ?? ''));
            if ($url === '' || ! $this->isXKomUrl($url)) {
                continue;
            }

            $cacheKey = 'xkom_info_' . sha1($url);
            $item = $this->cache->getItem($cacheKey);
            if ($item->isHit()) {
                $cached = $item->get();
                if (is_array($cached)) {
                    $result[$product->getId()] = new XKomProductInfo(
                        isset($cached['price']) && is_numeric($cached['price']) ? (int) $cached['price'] : null,
                        (bool) ($cached['available'] ?? false),
                        isset($cached['title']) && is_string($cached['title']) ? $cached['title'] : null,
                    );
                    continue;
                }
            }

            try {
                $response = $this->httpClient->request('GET', $url, [
                    'headers' => [
                        'User-Agent'      => self::USER_AGENT,
                        'Accept-Language' => 'pl-PL,pl;q=0.9',
                        'Accept'          => 'text/html,application/xhtml+xml',
                    ],
                    'timeout' => 8,
                ]);
                $statusCode = $response->getStatusCode();
                $html = $response->getContent(false);
                if ($statusCode >= 400) {
                    $this->logger->warning('x-kom fetch returned HTTP error.', [
                        'productId'  => $product->getId(),
                        'url'        => $url,
                        'statusCode' => $statusCode,
                    ]);
                    $result[$product->getId()] = null;
                    continue;
                }

                $info = $this->parseFromHtml($html);
                if ($info->price === null && $info->title === null) {
                    $this->logger->warning('x-kom HTML parsed without product data (blocked or changed markup).', [
                        'productId'  => $product->getId(),
                        'url'        => $url,
                        'statusCode' => $statusCode,
                        'htmlLength' => strlen($html),
                    ]);
                }

                $result[$product->getId()] = $info;

                $item->set([
                    'price'     => $info->price,
                    'available' => $info->available,
                    'title'     => $info->title,
                ]);
                $item->expiresAfter(self::CACHE_TTL_SECONDS);
                $this->cache->save($item);
            } catch (\Throwable $e) {
                $this->logger->error('x-kom fetch failed.', [
                    'productId' => $product->getId(),
                    'url'       => $url,
                    'exception' => $e,
                ]);
                $result[$product->getId()] = null;
            }
        }

        return $result;
    }

    public function isXKomUrl(string $url): bool
    {
        $host = parse_url(trim($url), PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            return false;
        }

        $host = strtolower($host);

        return $host === 'x-kom.pl' || str_ends_with($host, '.x-kom.pl');
    }

    private function parseFromHtml(string $html): XKomProductInfo
    {
        $product = $this->extractJsonLdProduct($html);
        $price = $this->priceFromOffers($product['offers'] ?? null);
        $title = isset($product['name']) && is_string($product['name']) ? trim($product['name']) : null;
        if ($title === '') {
            $title = null;
        }
        $availability = is_array($product['offers'] ?? null)
            ? ($product['offers']['availability'] ?? null)
            : null;

        $productId = $product['productID'] ?? $product['sku'] ?? null;
        $embedded = $this->parseEmbeddedAvailability($html, $productId);

        $available = $this->isAvailableFromSchema($availability, $embedded['status'] ?? null);

        return new XKomProductInfo(
            $price,
            $available,
            $title,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function extractJsonLdProduct(string $html): array
    {
        if (! preg_match_all('#<script[^>]+type="application/ld\+json"[^>]*>(.*?)</script>#is', $html, $matches)) {
            return [];
        }

        foreach ($matches[1] as $json) {
            $data = json_decode(html_entity_decode(trim($json), ENT_QUOTES | ENT_HTML5, 'UTF-8'), true);
            if (! is_array($data)) {
                continue;
            }

            if (($data['@type'] ?? null) === 'Product') {
                return $data;
            }

            foreach ($data['@graph'] ?? [] as $node) {
                if (is_array($node) && ($node['@type'] ?? null) === 'Product') {
                    return $node;
                }
            }
        }

        return [];
    }

    /**
     * @return array{status: ?string, text: ?string}
     */
    private function parseEmbeddedAvailability(string $html, mixed $productId): array
    {
        $status = null;
        if (preg_match('/"availabilityStatus"\s*:\s*"([^"]+)"/', $html, $match)) {
            $status = $match[1];
        }

        $text = null;
        if (
            preg_match(
                '/"availabilityCode"\s*:\s*"unavailable".{0,1200}?"availabilityText"\s*:\s*"([^"]+)"/s',
                $html,
                $match,
            )
        ) {
            $text = $match[1];
        } elseif (
            preg_match(
                '/"availabilityText"\s*:\s*"([^"]+)".{0,1200}?"availabilityCode"\s*:\s*"unavailable"/s',
                $html,
                $match,
            )
        ) {
            $text = $match[1];
        } elseif (preg_match('/"availabilityText"\s*:\s*"([^"]+)"/', $html, $match)) {
            $text = $match[1];
        }

        return ['status' => $status, 'text' => $text];
    }

    private function isAvailableFromSchema(mixed $availability, ?string $embeddedStatus): bool
    {
        if (is_string($embeddedStatus) && strcasecmp($embeddedStatus, 'Unavailable') === 0) {
            return false;
        }

        if (! is_string($availability) || $availability === '') {
            return false;
        }

        $availability = strtolower($availability);

        foreach (['instock', 'limitedavailability', 'preorder', 'onlineonly'] as $inStockMarker) {
            if (str_contains($availability, $inStockMarker)) {
                return true;
            }
        }

        return false;
    }

    private function priceFromOffers(mixed $offers): ?int
    {
        if (! is_array($offers) || ! isset($offers['price'])) {
            return null;
        }

        if (! is_numeric($offers['price'])) {
            return null;
        }

        return (int) round((float) $offers['price']);
    }
}
