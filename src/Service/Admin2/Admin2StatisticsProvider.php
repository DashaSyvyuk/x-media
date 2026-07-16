<?php

namespace App\Service\Admin2;

use App\Entity\Order;
use App\Entity\Product;
use App\Repository\CirculationRepository;
use App\Repository\DebtorRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

final class Admin2StatisticsProvider
{
    public const PERIOD_7 = '7';
    public const PERIOD_30 = '30';
    public const PERIOD_90 = '90';
    public const PERIOD_365 = '365';

    public const PERIODS = [
        self::PERIOD_7   => '7 днів',
        self::PERIOD_30  => '30 днів',
        self::PERIOD_90  => '90 днів',
        self::PERIOD_365 => 'Рік',
    ];

    private const CANCELED_STATUSES = [
        Order::CANCELED_NOT_CONFIRMED,
        Order::CANCELED_NO_PRODUCT,
        Order::CANCELED_NOT_PICKED_UP,
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Connection $connection,
        private readonly RozetkaSellerApiClient $rozetkaApiClient,
        private readonly CirculationRepository $circulationRepository,
        private readonly DebtorRepository $debtorRepository,
    ) {
    }

    public function normalizePeriod(?string $period): string
    {
        if ($period !== null && isset(self::PERIODS[$period])) {
            return $period;
        }

        return self::PERIOD_30;
    }

    /**
     * @return array{
     *     period: string,
     *     periodLabel: string,
     *     from: \DateTimeImmutable,
     *     to: \DateTimeImmutable,
     *     kpi: array{orders: int, revenue: int, avgCheck: int, cancelRate: float, paid: int, activeProducts: int},
     *     daily: array{labels: list<string>, orders: list<int>, revenue: list<int>},
     *     statusGroups: array{labels: list<string>, values: list<int>, colors: list<string>},
     *     sources: array{labels: list<string>, values: list<int>},
     *     topProducts: array{labels: list<string>, values: list<int>}
     * }
     */
    public function buildOrders(string $period): array
    {
        $period = $this->normalizePeriod($period);
        $days = (int) $period;
        $to = new \DateTimeImmutable('today 23:59:59');
        $from = $to->modify(sprintf('-%d days', $days - 1))->setTime(0, 0);
        $rozetkaOrders = $this->rozetkaApiClient->countOrdersCreatedBetween($from, $to);

        return [
            'period'       => $period,
            'periodLabel'  => self::PERIODS[$period],
            'from'         => $from,
            'to'           => $to,
            'kpi'          => $this->buildKpi($from, $to, $rozetkaOrders),
            'daily'        => $this->buildDailySeries($from, $to),
            'statusGroups' => $this->buildStatusGroups($from, $to),
            'sources'      => $this->buildSources($from, $to, $rozetkaOrders),
            'topProducts'  => $this->buildTopProducts($from, $to),
        ];
    }

    /**
     * @return array{
     *     circulations: array{
     *         accounts: int,
     *         owedToYouByCurrency: list<array{code: string, total: int}>,
     *         youOweByCurrency: list<array{code: string, total: int}>,
     *         chart: array{labels: list<string>, values: list<int>, codes: list<string>}
     *     },
     *     debtors: array{
     *         contacts: int,
     *         owedToYouByCurrency: list<array{code: string, total: int}>,
     *         youOweByCurrency: list<array{code: string, total: int}>,
     *         chart: array{labels: list<string>, values: list<int>, codes: list<string>}
     *     }|null
     * }
     */
    public function buildFinance(bool $includeDebts = false): array
    {
        $circulations = $this->circulationRepository->getFinanceSummary(true);
        $circulationRows = $this->circulationRepository->getActiveBalancesForChart(24);

        $debtors = null;
        if ($includeDebts) {
            $debtorsSummary = $this->debtorRepository->getFinanceSummary(true);
            $debtorRows = $this->debtorRepository->getActiveBalancesForChart(24);
            $debtors = [
                ...$debtorsSummary,
                'chart' => $this->rowsToChart($debtorRows),
            ];
        }

        return [
            'circulations' => [
                ...$circulations,
                'chart' => $this->rowsToChart($circulationRows),
            ],
            'debtors' => $debtors,
        ];
    }

    /**
     * @param list<array{id: int, label: string, code: string, balance: int}> $rows
     *
     * @return array{labels: list<string>, values: list<int>, codes: list<string>}
     */
    private function rowsToChart(array $rows): array
    {
        $labels = [];
        $values = [];
        $codes = [];

        foreach ($rows as $row) {
            $label = $row['label'];
            if (mb_strlen($label) > 28) {
                $label = mb_substr($label, 0, 26) . '…';
            }
            $labels[] = $label;
            $values[] = $row['balance'];
            $codes[] = $row['code'];
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'codes'  => $codes,
        ];
    }

    /**
     * @return array{orders: int, revenue: int, avgCheck: int, cancelRate: float, paid: int, activeProducts: int}
     */
    private function buildKpi(\DateTimeImmutable $from, \DateTimeImmutable $to, int $rozetkaOrders = 0): array
    {
        $row = $this->connection->fetchAssociative(
            'SELECT
                COUNT(*) AS orders_count,
                COALESCE(SUM(CASE WHEN status = :completed THEN total ELSE 0 END), 0) AS revenue,
                COALESCE(SUM(CASE WHEN status IN (:canceled) THEN 1 ELSE 0 END), 0) AS canceled_count,
                COALESCE(SUM(CASE WHEN payment_status = 1 THEN 1 ELSE 0 END), 0) AS paid_count
             FROM orders
             WHERE created_at BETWEEN :from AND :to',
            [
                'completed' => Order::COMPLETED,
                'canceled'  => self::CANCELED_STATUSES,
                'from'      => $from->format('Y-m-d H:i:s'),
                'to'        => $to->format('Y-m-d H:i:s'),
            ],
            [
                'canceled' => ArrayParameterType::STRING,
            ],
        ) ?: [];

        $orders = (int) ($row['orders_count'] ?? 0) + max(0, $rozetkaOrders);
        $revenue = (int) ($row['revenue'] ?? 0);
        $canceled = (int) ($row['canceled_count'] ?? 0);
        $paid = (int) ($row['paid_count'] ?? 0);
        $completedCount = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM orders WHERE created_at BETWEEN :from AND :to AND status = :completed',
            [
                'from'      => $from->format('Y-m-d H:i:s'),
                'to'        => $to->format('Y-m-d H:i:s'),
                'completed' => Order::COMPLETED,
            ],
        );

        $activeProducts = (int) $this->entityManager->getRepository(Product::class)->count([
            'status' => Product::STATUS_ACTIVE,
        ]);

        return [
            'orders'         => $orders,
            'revenue'        => $revenue,
            'avgCheck'       => $completedCount > 0 ? (int) round($revenue / $completedCount) : 0,
            'cancelRate'     => $orders > 0 ? round(($canceled / $orders) * 100, 1) : 0.0,
            'paid'           => $paid,
            'activeProducts' => $activeProducts,
        ];
    }

    /**
     * @return array{labels: list<string>, orders: list<int>, revenue: list<int>}
     */
    private function buildDailySeries(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT DATE(created_at) AS day,
                    COUNT(*) AS orders_count,
                    COALESCE(SUM(CASE WHEN status = :completed THEN total ELSE 0 END), 0) AS revenue
             FROM orders
             WHERE created_at BETWEEN :from AND :to
             GROUP BY DATE(created_at)
             ORDER BY day ASC',
            [
                'completed' => Order::COMPLETED,
                'from'      => $from->format('Y-m-d H:i:s'),
                'to'        => $to->format('Y-m-d H:i:s'),
            ],
        );

        $byDay = [];
        foreach ($rows as $row) {
            $byDay[(string) $row['day']] = [
                'orders'  => (int) $row['orders_count'],
                'revenue' => (int) $row['revenue'],
            ];
        }

        $labels = [];
        $orders = [];
        $revenue = [];
        $cursor = $from;
        while ($cursor <= $to) {
            $key = $cursor->format('Y-m-d');
            $labels[] = $cursor->format('d.m');
            $orders[] = $byDay[$key]['orders'] ?? 0;
            $revenue[] = $byDay[$key]['revenue'] ?? 0;
            $cursor = $cursor->modify('+1 day');
        }

        return [
            'labels'  => $labels,
            'orders'  => $orders,
            'revenue' => $revenue,
        ];
    }

    /**
     * @return array{labels: list<string>, values: list<int>, colors: list<string>}
     */
    private function buildStatusGroups(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT status, COUNT(*) AS cnt
             FROM orders
             WHERE created_at BETWEEN :from AND :to
             GROUP BY status',
            [
                'from' => $from->format('Y-m-d H:i:s'),
                'to'   => $to->format('Y-m-d H:i:s'),
            ],
        );

        $groups = [
            'Нове'       => ['count' => 0, 'color' => '#0d9488'],
            'В процесі'  => ['count' => 0, 'color' => '#f59e0b'],
            'Відправлено'=> ['count' => 0, 'color' => '#3b82f6'],
            'Доставлено' => ['count' => 0, 'color' => '#0f172a'],
            'Відмінено'  => ['count' => 0, 'color' => '#94a3b8'],
        ];

        foreach ($rows as $row) {
            $status = (string) $row['status'];
            $title = Order::GROUPED_STATUSES[$status]['title'] ?? 'Інше';
            if (! isset($groups[$title])) {
                $groups[$title] = ['count' => 0, 'color' => '#64748b'];
            }
            $groups[$title]['count'] += (int) $row['cnt'];
        }

        $labels = [];
        $values = [];
        $colors = [];
        foreach ($groups as $label => $data) {
            if ($data['count'] <= 0) {
                continue;
            }
            $labels[] = $label;
            $values[] = $data['count'];
            $colors[] = $data['color'];
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'colors' => $colors,
        ];
    }

    /**
     * @return array{labels: list<string>, values: list<int>}
     */
    private function buildSources(\DateTimeImmutable $from, \DateTimeImmutable $to, int $rozetkaOrders = 0): array
    {
        $xmediaCount = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM orders WHERE created_at BETWEEN :from AND :to',
            [
                'from' => $from->format('Y-m-d H:i:s'),
                'to'   => $to->format('Y-m-d H:i:s'),
            ],
        );

        return [
            'labels' => ['Rozetka', 'x-media'],
            'values' => [max(0, $rozetkaOrders), $xmediaCount],
        ];
    }

    /**
     * @return array{labels: list<string>, values: list<int>}
     */
    private function buildTopProducts(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT p.title AS product_title, COALESCE(SUM(oi.count), 0) AS qty
             FROM order_item oi
             INNER JOIN orders o ON o.id = oi.order_id
             INNER JOIN product p ON p.id = oi.product_id
             WHERE o.created_at BETWEEN :from AND :to
               AND o.status NOT IN (:canceled)
             GROUP BY p.id, p.title
             ORDER BY qty DESC
             LIMIT 10',
            [
                'from'     => $from->format('Y-m-d H:i:s'),
                'to'       => $to->format('Y-m-d H:i:s'),
                'canceled' => self::CANCELED_STATUSES,
            ],
            [
                'canceled' => ArrayParameterType::STRING,
            ],
        );

        $labels = [];
        $values = [];
        foreach ($rows as $row) {
            $title = trim((string) $row['product_title']);
            if (mb_strlen($title) > 42) {
                $title = mb_substr($title, 0, 40) . '…';
            }
            $labels[] = $title !== '' ? $title : 'Без назви';
            $values[] = (int) $row['qty'];
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }
}
