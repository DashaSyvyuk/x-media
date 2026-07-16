<?php

namespace App\Service\Admin2;

use App\Entity\FopProfile;
use App\Entity\Order;
use App\Repository\AdminPlanRepository;
use App\Repository\DebtorRepository;
use App\Repository\FopProfileRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

final class Admin2DashboardProvider
{
    private const ACTIVE_ORDER_STATUSES = [
        Order::NEW,
        Order::NOT_APPROVED,
        Order::APPROVED,
        Order::PACKING,
        Order::NOVA_POSHTA_DELIVERING,
        Order::COURIER_DELIVERING,
    ];

    private const CANCELED_STATUSES = [
        Order::CANCELED_NOT_CONFIRMED,
        Order::CANCELED_NO_PRODUCT,
        Order::CANCELED_NOT_PICKED_UP,
    ];

    public function __construct(
        private readonly Connection $connection,
        private readonly NbpExchangeRateService $exchangeRateService,
        private readonly FopProfileRepository $fopProfileRepository,
        private readonly DebtorRepository $debtorRepository,
        private readonly AdminPlanRepository $adminPlanRepository,
        private readonly RozetkaSellerApiClient $rozetkaApiClient,
    ) {
    }

    /**
     * @return array{
     *     exchangeRates: array{updated_at: ?string, pairs: list<array{label: string, from: string, to: string, value: float|null}>, error: ?string},
     *     today: array{orders: int, revenue: int, paid: int},
     *     month: array{orders: int, revenue: int, paid: int, cancelRate: float},
     *     monthLabel: string,
     *     circulationToday: array{
     *         incomeByCurrency: list<array{code: string, total: int}>,
     *         expenseByCurrency: list<array{code: string, total: int}>,
     *         netByCurrency: list<array{code: string, total: int}>,
     *         payments: list<array{id: int, sum: int, note: string, description: string, createdAt: string, cash: string, code: string}>
     *     },
     *     activeOrders: list<array{id: int, orderNumber: string, customer: string, phone: string, status: string, statusLabel: string, total: int, createdAt: string}>,
     *     fops: list<array{id: int, title: string}>,
     *     debts: list<array{id: int, name: string, code: string, balance: int}>,
     *     todayPlans: list<array{id: int, title: string, body: string, assignee: string}>
     * }
     */
    public function build(bool $withDebts = false): array
    {
        $todayFrom = new \DateTimeImmutable('today 00:00:00');
        $todayTo = new \DateTimeImmutable('today 23:59:59');
        $monthFrom = $todayFrom->modify('first day of this month')->setTime(0, 0);

        return [
            'exchangeRates'    => $this->exchangeRateService->dashboardRates(),
            'today'            => $this->orderKpi($todayFrom, $todayTo),
            'month'            => $this->orderMonthKpi($monthFrom, $todayTo),
            'monthLabel'       => $this->monthLabel($monthFrom),
            'circulationToday' => $this->circulationToday($todayFrom, $todayTo),
            'activeOrders'     => $this->activeOrders(12),
            'fops'             => $this->fopNotes(),
            'debts'            => $withDebts ? $this->activeDebts(12) : [],
            'todayPlans'       => $this->todayPlans($todayFrom),
        ];
    }

    /**
     * @return array{orders: int, revenue: int, paid: int}
     */
    private function orderKpi(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $row = $this->connection->fetchAssociative(
            'SELECT
                COUNT(*) AS orders_count,
                COALESCE(SUM(CASE WHEN status = :completed THEN total ELSE 0 END), 0) AS revenue,
                COALESCE(SUM(CASE WHEN payment_status = 1 THEN 1 ELSE 0 END), 0) AS paid_count
             FROM orders
             WHERE created_at BETWEEN :from AND :to',
            [
                'completed' => Order::COMPLETED,
                'from'      => $from->format('Y-m-d H:i:s'),
                'to'        => $to->format('Y-m-d H:i:s'),
            ],
        ) ?: [];

        $localOrders = (int) ($row['orders_count'] ?? 0);
        $rozetkaOrders = $this->rozetkaApiClient->countOrdersCreatedBetween($from, $to);

        return [
            'orders'  => $localOrders + max(0, $rozetkaOrders),
            'revenue' => (int) ($row['revenue'] ?? 0),
            'paid'    => (int) ($row['paid_count'] ?? 0),
        ];
    }

    /**
     * @return array{orders: int, revenue: int, paid: int, cancelRate: float}
     */
    private function orderMonthKpi(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $base = $this->orderKpi($from, $to);
        $canceled = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM orders
             WHERE created_at BETWEEN :from AND :to
               AND status IN (:canceled)',
            [
                'from'     => $from->format('Y-m-d H:i:s'),
                'to'       => $to->format('Y-m-d H:i:s'),
                'canceled' => self::CANCELED_STATUSES,
            ],
            [
                'canceled' => ArrayParameterType::STRING,
            ],
        );

        $orders = max(0, $base['orders']);

        return [
            ...$base,
            'cancelRate' => $orders > 0 ? round(($canceled / $orders) * 100, 1) : 0.0,
        ];
    }

    /**
     * @return array{
     *     incomeByCurrency: list<array{code: string, total: int}>,
     *     expenseByCurrency: list<array{code: string, total: int}>,
     *     netByCurrency: list<array{code: string, total: int}>,
     *     payments: list<array{id: int, sum: int, note: string, description: string, createdAt: string, cash: string, code: string}>
     * }
     */
    private function circulationToday(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT
                cur.code AS currency_code,
                COALESCE(SUM(CASE WHEN p.sum > 0 THEN p.sum ELSE 0 END), 0) AS income_total,
                COALESCE(SUM(CASE WHEN p.sum < 0 THEN ABS(p.sum) ELSE 0 END), 0) AS expense_total,
                COALESCE(SUM(p.sum), 0) AS net_total
             FROM circulation_payments p
             INNER JOIN circulations c ON c.id = p.circulation_id
             INNER JOIN currency cur ON cur.id = c.currency_id
             WHERE p.created_at BETWEEN :from AND :to
             GROUP BY cur.id, cur.code
             ORDER BY cur.code ASC',
            [
                'from' => $from->format('Y-m-d H:i:s'),
                'to'   => $to->format('Y-m-d H:i:s'),
            ],
        );

        $income = [];
        $expense = [];
        $net = [];
        foreach ($rows as $row) {
            $code = (string) $row['currency_code'];
            $incomeTotal = (int) $row['income_total'];
            $expenseTotal = (int) $row['expense_total'];
            $netTotal = (int) $row['net_total'];
            if ($incomeTotal > 0) {
                $income[] = ['code' => $code, 'total' => $incomeTotal];
            }
            if ($expenseTotal > 0) {
                $expense[] = ['code' => $code, 'total' => $expenseTotal];
            }
            if ($netTotal !== 0) {
                $net[] = ['code' => $code, 'total' => $netTotal];
            }
        }

        $paymentRows = $this->connection->fetchAllAssociative(
            'SELECT
                p.id,
                p.sum,
                COALESCE(p.note, \'\') AS note,
                COALESCE(p.description, \'\') AS description,
                p.created_at,
                cur.code AS currency_code,
                COALESCE(NULLIF(TRIM(au.name), \'\'), NULLIF(TRIM(au.email), \'\'), CONCAT(\'Каса #\', c.id)) AS cash_label
             FROM circulation_payments p
             INNER JOIN circulations c ON c.id = p.circulation_id
             INNER JOIN currency cur ON cur.id = c.currency_id
             LEFT JOIN admin_user au ON au.id = c.admin_user_id
             WHERE p.created_at BETWEEN :from AND :to
             ORDER BY p.created_at DESC, p.id DESC
             LIMIT 20',
            [
                'from' => $from->format('Y-m-d H:i:s'),
                'to'   => $to->format('Y-m-d H:i:s'),
            ],
        );

        $payments = [];
        foreach ($paymentRows as $row) {
            $payments[] = [
                'id'          => (int) $row['id'],
                'sum'         => (int) $row['sum'],
                'note'        => (string) $row['note'],
                'description' => (string) $row['description'],
                'createdAt'   => substr((string) $row['created_at'], 0, 16),
                'cash'        => (string) $row['cash_label'],
                'code'        => (string) $row['currency_code'],
            ];
        }

        return [
            'incomeByCurrency'  => $income,
            'expenseByCurrency' => $expense,
            'netByCurrency'     => $net,
            'payments'          => $payments,
        ];
    }

    /**
     * @return list<array{id: int, orderNumber: string, customer: string, phone: string, status: string, statusLabel: string, total: int, createdAt: string}>
     */
    private function activeOrders(int $limit): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, order_number, name, surname, phone, status, total, created_at
             FROM orders
             WHERE status IN (:statuses)
             ORDER BY created_at DESC
             LIMIT ' . max(1, $limit),
            [
                'statuses' => self::ACTIVE_ORDER_STATUSES,
            ],
            [
                'statuses' => ArrayParameterType::STRING,
            ],
        );

        $result = [];
        foreach ($rows as $row) {
            $status = (string) $row['status'];
            $result[] = [
                'id'          => (int) $row['id'],
                'orderNumber' => (string) $row['order_number'],
                'customer'    => trim((string) $row['surname'] . ' ' . (string) $row['name']),
                'phone'       => (string) ($row['phone'] ?? ''),
                'status'      => $status,
                'statusLabel' => Order::STATUSES[$status] ?? $status,
                'total'       => (int) $row['total'],
                'createdAt'   => substr((string) $row['created_at'], 0, 16),
            ];
        }

        return $result;
    }

    /**
     * @return list<array{id: int, title: string}>
     */
    private function fopNotes(): array
    {
        /** @var list<FopProfile> $fops */
        $fops = $this->fopProfileRepository->findBy([], ['id' => 'DESC']);
        $result = [];
        foreach ($fops as $fop) {
            $title = trim((string) $fop->getTitle());
            if ($title === '') {
                continue;
            }
            $result[] = [
                'id'    => $fop->getId(),
                'title' => $title,
            ];
        }

        return $result;
    }

    /**
     * @return list<array{id: int, name: string, code: string, balance: int}>
     */
    private function activeDebts(int $limit): array
    {
        $rows = $this->debtorRepository->getActiveBalancesForChart(max($limit * 3, 30));
        $withBalance = array_values(array_filter(
            $rows,
            static fn (array $row): bool => (int) $row['balance'] !== 0,
        ));

        usort(
            $withBalance,
            static fn (array $a, array $b): int => abs((int) $b['balance']) <=> abs((int) $a['balance']),
        );

        $result = [];
        foreach (array_slice($withBalance, 0, $limit) as $row) {
            $result[] = [
                'id'      => (int) $row['id'],
                'name'    => (string) $row['label'],
                'code'    => (string) $row['code'],
                'balance' => (int) $row['balance'],
            ];
        }

        return $result;
    }

    /**
     * @return list<array{id: int, title: string, body: string, assignee: string}>
     */
    private function todayPlans(\DateTimeImmutable $today): array
    {
        $result = [];
        foreach ($this->adminPlanRepository->findTodayPending($today) as $plan) {
            $assignee = $plan->getAssignee();
            $result[] = [
                'id'       => (int) $plan->getId(),
                'title'    => $plan->getTitle(),
                'body'     => trim((string) ($plan->getBody() ?? '')),
                'assignee' => trim($assignee->getName() . ' ' . $assignee->getSurname()),
            ];
        }

        return $result;
    }

    private function monthLabel(\DateTimeImmutable $monthFrom): string
    {
        $months = [
            1 => 'січня', 2 => 'лютого', 3 => 'березня', 4 => 'квітня',
            5 => 'травня', 6 => 'червня', 7 => 'липня', 8 => 'серпня',
            9 => 'вересня', 10 => 'жовтня', 11 => 'листопада', 12 => 'грудня',
        ];

        $month = (int) $monthFrom->format('n');

        return 'з 1 ' . ($months[$month] ?? $monthFrom->format('m')) . ' ' . $monthFrom->format('Y');
    }
}
