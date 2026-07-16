<?php

namespace App\Service\Admin2;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class NbpExchangeRateService
{
    private const CACHE_KEY = 'dashboard_exchange_rates_v2';
    private const CACHE_TTL = 3600;
    private const NBP_URL = 'https://api.nbp.pl/api/exchangerates/tables/A';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheItemPoolInterface $cache,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return array{
     *     updated_at: ?string,
     *     pairs: list<array{label: string, from: string, to: string, value: float|null}>,
     *     error: ?string
     * }
     */
    public function dashboardRates(): array
    {
        $item = $this->cache->getItem(self::CACHE_KEY);
        if ($item->isHit()) {
            $cached = $item->get();

            return is_array($cached) ? $cached : $this->errorResult('Кеш курсів пошкоджено.');
        }

        $result = $this->fetchRates();
        $item->set($result);
        $item->expiresAfter(self::CACHE_TTL);
        $this->cache->save($item);

        return $result;
    }

    public static function formatRate(?float $value): string
    {
        if ($value === null) {
            return '—';
        }

        $decimals = $value >= 1 ? 2 : 4;

        return rtrim(rtrim(number_format($value, $decimals, '.', ' '), '0'), '.');
    }

    /**
     * @return array{
     *     updated_at: ?string,
     *     pairs: list<array{label: string, from: string, to: string, value: float|null}>,
     *     error: ?string
     * }
     */
    private function fetchRates(): array
    {
        try {
            $response = $this->httpClient->request('GET', self::NBP_URL, [
                'query'   => ['format' => 'json'],
                'timeout' => 8,
            ]);

            if ($response->getStatusCode() >= 400) {
                return $this->errorResult('Не вдалося отримати курси NBP.');
            }

            $payload = $response->toArray(false);
            $table = is_array($payload[0] ?? null) ? $payload[0] : null;
            $rates = is_array($table['rates'] ?? null) ? $table['rates'] : [];
            $codes = [];
            foreach ($rates as $rate) {
                if (! is_array($rate) || ! isset($rate['code'])) {
                    continue;
                }
                $codes[(string) $rate['code']] = $rate;
            }

            $usdPln = $this->mid($codes, 'USD');
            $eurPln = $this->mid($codes, 'EUR');
            $uahPln = $this->mid($codes, 'UAH');

            if ($usdPln === null || $eurPln === null || $uahPln === null) {
                return $this->errorResult('У таблиці NBP немає USD, EUR або UAH.');
            }

            $plnUah = $uahPln > 0 ? round(1 / $uahPln, 4) : null;
            $usdUah = ($usdPln > 0 && $uahPln > 0) ? round($usdPln / $uahPln, 4) : null;
            $eurUah = ($eurPln > 0 && $uahPln > 0) ? round($eurPln / $uahPln, 4) : null;

            return [
                'updated_at' => is_string($table['effectiveDate'] ?? null) ? $table['effectiveDate'] : null,
                'error'      => null,
                'pairs'      => [
                    $this->pair('USD', 'PLN', $usdPln),
                    $this->pair('EUR', 'PLN', $eurPln),
                    $this->pair('PLN', 'UAH', $plnUah),
                    $this->pair('USD', 'UAH', $usdUah),
                    $this->pair('EUR', 'UAH', $eurUah),
                ],
            ];
        } catch (\Throwable $e) {
            $this->logger->warning('NBP exchange rates fetch failed.', ['exception' => $e]);

            return $this->errorResult('Помилка зʼєднання з NBP.');
        }
    }

    /**
     * @param array<string, array<string, mixed>> $codes
     */
    private function mid(array $codes, string $code): ?float
    {
        $rate = $codes[$code] ?? null;
        if (! is_array($rate) || ! isset($rate['mid']) || ! is_numeric($rate['mid'])) {
            return null;
        }

        return round((float) $rate['mid'], 4);
    }

    /**
     * @return array{label: string, from: string, to: string, value: float|null}
     */
    private function pair(string $from, string $to, ?float $value): array
    {
        return [
            'label' => $from . ' → ' . $to,
            'from'  => $from,
            'to'    => $to,
            'value' => $value,
        ];
    }

    /**
     * @return array{
     *     updated_at: ?string,
     *     pairs: list<array{label: string, from: string, to: string, value: float|null}>,
     *     error: ?string
     * }
     */
    private function errorResult(string $message): array
    {
        return [
            'updated_at' => null,
            'error'      => $message,
            'pairs'      => [
                $this->pair('USD', 'PLN', null),
                $this->pair('EUR', 'PLN', null),
                $this->pair('PLN', 'UAH', null),
                $this->pair('USD', 'UAH', null),
                $this->pair('EUR', 'UAH', null),
            ],
        ];
    }
}
