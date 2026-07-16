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
    public const PERIOD_TODAY = 'today';
    public const PERIOD_MONTH = 'month';
    public const PERIOD_7 = '7';
    public const PERIOD_30 = '30';
    public const PERIOD_90 = '90';
    public const PERIOD_365 = '365';

    public const PERIODS = [
        self::PERIOD_TODAY => 'Сьогодні',
        self::PERIOD_MONTH => 'Поточний місяць',
        self::PERIOD_7     => '7 днів',
        self::PERIOD_30    => '30 днів',
        self::PERIOD_90    => '90 днів',
        self::PERIOD_365   => 'Рік',
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
        private readonly RozetkaOrderPresenter $rozetkaOrderPresenter,
        private readonly RozetkaOrderPaymentResolver $rozetkaPaymentResolver,
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
     * @return array{0: \DateTimeImmutable, 1: \DateTimeImmutable}
     */
    public function resolvePeriodRange(string $period): array
    {
        $period = $this->normalizePeriod($period);
        $to = new \DateTimeImmutable('today 23:59:59');

        return match ($period) {
            self::PERIOD_TODAY => [
                $to->setTime(0, 0),
                $to,
            ],
            self::PERIOD_MONTH => [
                $to->modify('first day of this month')->setTime(0, 0),
                $to,
            ],
            default => [
                $to->modify(sprintf('-%d days', max(1, (int) $period) - 1))->setTime(0, 0),
                $to,
            ],
        };
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
        [$from, $to] = $this->resolvePeriodRange($period);
        $rozetkaOrdersList = $this->rozetkaApiClient->fetchOrdersCreatedBetween($from, $to);
        $rozetkaMarkers = [];
        $rozetkaMarkersActive = [];
        $rozetkaOrders = 0;
        $rozetkaPaid = 0;
        foreach ($rozetkaOrdersList as $apiOrder) {
            $marker = [
                'created' => (string) ($apiOrder['created'] ?? ''),
                'status'  => (int) ($apiOrder['status'] ?? 0),
            ];
            $rozetkaMarkers[] = $marker;

            if ($this->isRozetkaCanceled($apiOrder)) {
                continue;
            }

            $rozetkaMarkersActive[] = $marker;
            ++$rozetkaOrders;
            if ($this->rozetkaPaymentResolver->isPaid($apiOrder)) {
                ++$rozetkaPaid;
            }
        }

        return [
            'period'       => $period,
            'periodLabel'  => self::PERIODS[$period],
            'from'         => $from,
            'to'           => $to,
            'kpi'          => $this->buildKpi($from, $to, $rozetkaOrders, $rozetkaPaid),
            'daily'        => $this->buildDailySeries($from, $to, $rozetkaMarkersActive),
            'statusGroups' => $this->buildStatusGroups($from, $to, $rozetkaMarkers),
            'sources'      => $this->buildSources($from, $to, $rozetkaOrders),
            'topProducts'  => $this->buildTopProducts($from, $to, $rozetkaOrdersList),
        ];
    }

    /**
     * @return array{
     *     circulations: array{
     *         accounts: int,
     *         balanceByCurrency: list<array{code: string, total: int}>,
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
    private function buildKpi(
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
        int $rozetkaOrders = 0,
        int $rozetkaPaid = 0,
    ): array {
        $row = $this->connection->fetchAssociative(
            'SELECT
                COUNT(*) AS orders_total,
                COALESCE(SUM(CASE WHEN status NOT IN (:canceled) THEN 1 ELSE 0 END), 0) AS orders_count,
                COALESCE(SUM(CASE WHEN status = :completed THEN total ELSE 0 END), 0) AS revenue,
                COALESCE(SUM(CASE WHEN status IN (:canceled) THEN 1 ELSE 0 END), 0) AS canceled_count,
                COALESCE(SUM(
                    CASE WHEN payment_status = 1 AND status NOT IN (:canceled) THEN 1 ELSE 0 END
                ), 0) AS paid_count
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

        $localOrdersTotal = (int) ($row['orders_total'] ?? 0);
        $localOrders = (int) ($row['orders_count'] ?? 0);
        $revenue = (int) ($row['revenue'] ?? 0);
        $canceled = (int) ($row['canceled_count'] ?? 0);
        $paid = (int) ($row['paid_count'] ?? 0) + max(0, $rozetkaPaid);
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
            'orders'         => $localOrders + max(0, $rozetkaOrders),
            'revenue'        => $revenue,
            'avgCheck'       => $completedCount > 0 ? (int) round($revenue / $completedCount) : 0,
            // Cancel rate is local-only: Rozetka cancels are not available in the same shape.
            'cancelRate'     => $localOrdersTotal > 0 ? round(($canceled / $localOrdersTotal) * 100, 1) : 0.0,
            'paid'           => $paid,
            'activeProducts' => $activeProducts,
        ];
    }

    /**
     * @param list<array{created: string, status: int}> $rozetkaMarkers
     *
     * @return array{labels: list<string>, orders: list<int>, revenue: list<int>}
     */
    private function buildDailySeries(
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
        array $rozetkaMarkers = [],
    ): array {

        $rows = $this->connection->fetchAllAssociative(
            'SELECT DATE(created_at) AS day,
                    COUNT(*) AS orders_count,
                    COALESCE(SUM(CASE WHEN status = :completed THEN total ELSE 0 END), 0) AS revenue
             FROM orders
             WHERE created_at BETWEEN :from AND :to
               AND status NOT IN (:canceled)
             GROUP BY DATE(created_at)
             ORDER BY day ASC',
            [
                'completed' => Order::COMPLETED,
                'canceled'  => self::CANCELED_STATUSES,
                'from'      => $from->format('Y-m-d H:i:s'),
                'to'        => $to->format('Y-m-d H:i:s'),
            ],
            [
                'canceled' => ArrayParameterType::STRING,
            ],
        );

        $byDay = [];
        foreach ($rows as $row) {
            $byDay[(string) $row['day']] = [
                'orders'  => (int) $row['orders_count'],
                'revenue' => (int) $row['revenue'],
            ];
        }

        foreach ($rozetkaMarkers as $marker) {
            $created = (string) $marker['created'];
            if ($created === '') {
                continue;
            }
            $day = substr($created, 0, 10);
            if (! isset($byDay[$day])) {
                $byDay[$day] = ['orders' => 0, 'revenue' => 0];
            }
            ++$byDay[$day]['orders'];
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
     * @param list<array{created: string, status: int}> $rozetkaMarkers
     *
     * @return array{labels: list<string>, values: list<int>, colors: list<string>}
     */
    private function buildStatusGroups(
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
        array $rozetkaMarkers = [],
    ): array {

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
            'Нове'        => ['count' => 0, 'color' => '#0d9488'],
            'В процесі'   => ['count' => 0, 'color' => '#f59e0b'],
            'Відправлено' => ['count' => 0, 'color' => '#3b82f6'],
            'Доставлено'  => ['count' => 0, 'color' => '#0f172a'],
            'Відмінено'   => ['count' => 0, 'color' => '#94a3b8'],
        ];

        foreach ($rows as $row) {
            $status = (string) $row['status'];
            $title = Order::GROUPED_STATUSES[$status]['title'] ?? 'Інше';
            if (! isset($groups[$title])) {
                $groups[$title] = ['count' => 0, 'color' => '#64748b'];
            }
            $groups[$title]['count'] += (int) $row['cnt'];
        }

        foreach ($rozetkaMarkers as $marker) {
            $title = $this->rozetkaStatusGroupTitle((int) $marker['status']);
            if (! isset($groups[$title])) {
                $groups[$title] = ['count' => 0, 'color' => '#64748b'];
            }
            ++$groups[$title]['count'];
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

    private function rozetkaStatusGroupTitle(int $statusId): string
    {
        return match ($statusId) {
            1 => 'Нове',
            26, 2, 3, 4, 5 => 'В процесі',
            61, 62, 63, 64, 65, 66 => 'Відправлено',
            40, 50, 57 => 'Доставлено',
            13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25 => 'Відмінено',
            default => 'В процесі',
        };
    }

    /**
     * @param array<string, mixed> $apiOrder
     */
    private function isRozetkaCanceled(array $apiOrder): bool
    {
        if ((int) ($apiOrder['status_group'] ?? 0) === 3) {
            return true;
        }

        $statusId = (int) ($apiOrder['status'] ?? 0);

        return $statusId >= 13 && $statusId <= 25;
    }

    /**
     * @return array{labels: list<string>, values: list<int>}
     */
    private function buildSources(\DateTimeImmutable $from, \DateTimeImmutable $to, int $rozetkaOrders = 0): array
    {
        $xmediaCount = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM orders
             WHERE created_at BETWEEN :from AND :to
               AND status NOT IN (:canceled)',
            [
                'from'     => $from->format('Y-m-d H:i:s'),
                'to'       => $to->format('Y-m-d H:i:s'),
                'canceled' => self::CANCELED_STATUSES,
            ],
            [
                'canceled' => ArrayParameterType::STRING,
            ],
        );

        return [
            'labels' => ['Rozetka', 'x-media'],
            'values' => [max(0, $rozetkaOrders), $xmediaCount],
        ];
    }

    /**
     * @param list<array<string, mixed>> $rozetkaOrders
     *
     * @return array{labels: list<string>, values: list<int>}
     */
    private function buildTopProducts(
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
        array $rozetkaOrders = [],
    ): array {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT p.id AS product_id, p.title AS product_title, COALESCE(SUM(oi.count), 0) AS qty
             FROM order_item oi
             INNER JOIN orders o ON o.id = oi.order_id
             INNER JOIN product p ON p.id = oi.product_id
             WHERE o.created_at BETWEEN :from AND :to
               AND o.status NOT IN (:canceled)
             GROUP BY p.id, p.title',
            [
                'from'     => $from->format('Y-m-d H:i:s'),
                'to'       => $to->format('Y-m-d H:i:s'),
                'canceled' => self::CANCELED_STATUSES,
            ],
            [
                'canceled' => ArrayParameterType::STRING,
            ],
        );

        /** @var array<int, int> $qtyByProductId */
        $qtyByProductId = [];
        /** @var array<int, string> $titleByProductId */
        $titleByProductId = [];

        foreach ($rows as $row) {
            $productId = (int) $row['product_id'];
            if ($productId <= 0) {
                continue;
            }
            $qtyByProductId[$productId] = (int) $row['qty'];
            $titleByProductId[$productId] = trim((string) $row['product_title']);
        }

        foreach ($rozetkaOrders as $apiOrder) {
            // status_group 3 = unsuccessful / canceled marketplace orders
            if ((int) ($apiOrder['status_group'] ?? 0) === 3) {
                continue;
            }

            $purchases = $apiOrder['purchases'] ?? [];
            if (! is_array($purchases)) {
                continue;
            }

            foreach ($purchases as $purchase) {
                if (! is_array($purchase)) {
                    continue;
                }

                $productId = $this->rozetkaOrderPresenter->resolveLocalProductId($purchase);
                if ($productId === null) {
                    continue;
                }

                $qty = max(1, (int) ($purchase['quantity'] ?? $purchase['count'] ?? 1));
                $qtyByProductId[$productId] = ($qtyByProductId[$productId] ?? 0) + $qty;

                if (! isset($titleByProductId[$productId])) {
                    $titleByProductId[$productId] = trim((string) (
                        $purchase['item_name'] ?? $purchase['name'] ?? ''
                    ));
                }
            }
        }

        $missingTitleIds = [];
        foreach ($qtyByProductId as $productId => $_) {
            if (($titleByProductId[$productId] ?? '') === '') {
                $missingTitleIds[] = $productId;
            }
        }

        if ($missingTitleIds !== []) {
            $titleRows = $this->connection->fetchAllAssociative(
                'SELECT id, title FROM product WHERE id IN (:ids)',
                ['ids' => $missingTitleIds],
                ['ids' => ArrayParameterType::INTEGER],
            );
            foreach ($titleRows as $titleRow) {
                $id = (int) $titleRow['id'];
                $titleByProductId[$id] = trim((string) $titleRow['title']);
            }
        }

        arsort($qtyByProductId, SORT_NUMERIC);
        $qtyByProductId = array_slice($qtyByProductId, 0, 10, true);

        $labels = [];
        $values = [];
        foreach ($qtyByProductId as $productId => $qty) {
            $title = $titleByProductId[$productId] ?? '';
            if ($title === '') {
                $title = 'Товар #' . $productId;
            }
            if (mb_strlen($title) > 42) {
                $title = mb_substr($title, 0, 40) . '…';
            }
            $labels[] = $title;
            $values[] = $qty;
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }
}
