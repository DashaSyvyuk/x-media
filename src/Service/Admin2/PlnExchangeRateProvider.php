<?php

namespace App\Service\Admin2;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class PlnExchangeRateProvider
{
    private const CACHE_KEY = 'pln_exchange_rate_uah';
    private const TTL_SECONDS = 6 * 60 * 60;
    private const NBU_URL = 'https://bank.gov.ua/NBUStatService/v1/statdirectory/exchange?valcode=PLN&json';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheItemPoolInterface $cache,
    ) {
    }

    /**
     * @return float|null UAH per 1 PLN
     */
    public function getRateUahPerPln(): ?float
    {
        $item = $this->cache->getItem(self::CACHE_KEY);
        if ($item->isHit()) {
            $cached = $item->get();
            return is_numeric($cached) ? (float) $cached : null;
        }

        try {
            $response = $this->httpClient->request('GET', self::NBU_URL, [
                'headers' => [
                    'Accept'          => 'application/json',
                    'Accept-Language' => 'uk-UA,uk;q=0.9,en;q=0.8',
                ],
                'timeout' => 8,
            ]);

            $data = $response->toArray(false);
            if (! is_array($data) || $data === []) {
                return null;
            }

            $row = is_array($data[0] ?? null) ? $data[0] : null;
            $rate = $row['rate'] ?? null;
            if (! is_numeric($rate)) {
                return null;
            }

            $rate = (float) $rate;
            if ($rate <= 0) {
                return null;
            }

            $item->set($rate);
            $item->expiresAfter(self::TTL_SECONDS);
            $this->cache->save($item);

            return $rate;
        } catch (\Throwable) {
            return null;
        }
    }
}

