<?php

namespace App\Repository;

use App\Entity\Circulation;
use App\Entity\CirculationPayment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Circulation>
 */
class CirculationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Circulation::class);
    }

    public function createAdminListQueryBuilder(
        string $search = '',
        ?bool $active = null,
        string $sort = 'total',
        string $direction = 'DESC',
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.currency', 'currency')
            ->addSelect('currency')
            ->leftJoin('c.adminUser', 'adminUser')
            ->addSelect('adminUser');

        $search = trim($search);
        if ($search !== '') {
            $qb->andWhere('LOWER(adminUser.email) LIKE :search OR LOWER(adminUser.name) LIKE :search')
                ->setParameter('search', '%' . mb_strtolower($search) . '%');
        }

        if ($active !== null) {
            $qb->andWhere('c.active = :active')->setParameter('active', $active);
        }

        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';

        if ($sort === 'total') {
            $qb->addSelect(
                '(SELECT COALESCE(SUM(p.sum), 0) FROM ' . CirculationPayment::class
                . ' p WHERE p.circulation = c) AS HIDDEN balanceSort',
            )->orderBy('balanceSort', $direction);
        } elseif ($sort === 'currency') {
            $qb->orderBy('currency.code', $direction);
        } elseif ($sort === 'admin') {
            $qb->orderBy('adminUser.email', $direction);
        } else {
            $allowedSorts = ['id', 'active', 'createdAt'];
            if (! in_array($sort, $allowedSorts, true)) {
                $sort = 'id';
            }
            $qb->orderBy('c.' . $sort, $direction);
        }

        return $qb;
    }

    /**
     * @param array<int, int> $circulationIds
     *
     * @return array<int, int>
     */
    public function getPaymentTotalsByCirculationIds(array $circulationIds): array
    {
        if ($circulationIds === []) {
            return [];
        }

        $rows = $this->getEntityManager()->createQueryBuilder()
            ->select('IDENTITY(p.circulation) AS circulationId', 'COALESCE(SUM(p.sum), 0) AS total')
            ->from(CirculationPayment::class, 'p')
            ->where('p.circulation IN (:ids)')
            ->groupBy('p.circulation')
            ->setParameter('ids', $circulationIds)
            ->getQuery()
            ->getScalarResult();

        $totals = [];
        foreach ($rows as $row) {
            $totals[(int) $row['circulationId']] = (int) $row['total'];
        }

        return $totals;
    }

    /**
     * @return array{
     *     accounts: int,
     *     owedToYouByCurrency: list<array{code: string, total: int}>,
     *     youOweByCurrency: list<array{code: string, total: int}>
     * }
     */
    public function getFinanceSummary(?bool $active = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->select(
                'currency.id AS currencyId',
                'currency.code AS currencyCode',
                '(SELECT COALESCE(SUM(p.sum), 0) FROM ' . CirculationPayment::class
                . ' p WHERE p.circulation = c) AS balance',
            )
            ->join('c.currency', 'currency');

        if ($active !== null) {
            $qb->andWhere('c.active = :active')->setParameter('active', $active);
        }

        $rows = $qb->getQuery()->getScalarResult();

        /** @var array<int, array{code: string, total: int}> $owedToYouByCurrency */
        $owedToYouByCurrency = [];
        /** @var array<int, array{code: string, total: int}> $youOweByCurrency */
        $youOweByCurrency = [];

        foreach ($rows as $row) {
            $currencyId = (int) $row['currencyId'];
            $balance    = (int) $row['balance'];
            $code       = (string) $row['currencyCode'];

            if ($balance > 0) {
                if (! isset($owedToYouByCurrency[$currencyId])) {
                    $owedToYouByCurrency[$currencyId] = ['code' => $code, 'total' => 0];
                }
                $owedToYouByCurrency[$currencyId]['total'] += $balance;
            } elseif ($balance < 0) {
                if (! isset($youOweByCurrency[$currencyId])) {
                    $youOweByCurrency[$currencyId] = ['code' => $code, 'total' => 0];
                }
                $youOweByCurrency[$currencyId]['total'] += abs($balance);
            }
        }

        $sortByCode = static fn (array $a, array $b): int => strcmp($a['code'], $b['code']);

        $owedList = array_values($owedToYouByCurrency);
        $oweList  = array_values($youOweByCurrency);
        usort($owedList, $sortByCode);
        usort($oweList, $sortByCode);

        $accountsQb = $this->createQueryBuilder('c')->select('COUNT(c.id)');
        if ($active !== null) {
            $accountsQb->andWhere('c.active = :active')->setParameter('active', $active);
        }

        return [
            'accounts'            => (int) $accountsQb->getQuery()->getSingleScalarResult(),
            'owedToYouByCurrency' => $owedList,
            'youOweByCurrency'    => $oweList,
        ];
    }

    /**
     * @return list<array{id: int, label: string, code: string, balance: int}>
     */
    public function getActiveBalancesForChart(int $limit = 20): array
    {
        $rows = $this->createQueryBuilder('c')
            ->select(
                'c.id AS id',
                'adminUser.email AS email',
                'adminUser.name AS name',
                'currency.code AS currencyCode',
                '(SELECT COALESCE(SUM(p.sum), 0) FROM ' . CirculationPayment::class
                . ' p WHERE p.circulation = c) AS balance',
            )
            ->addSelect(
                '(SELECT COALESCE(SUM(p2.sum), 0) FROM ' . CirculationPayment::class
                . ' p2 WHERE p2.circulation = c) AS HIDDEN balanceSort',
            )
            ->leftJoin('c.adminUser', 'adminUser')
            ->join('c.currency', 'currency')
            ->andWhere('c.active = :active')
            ->setParameter('active', true)
            ->orderBy('balanceSort', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getScalarResult();

        $result = [];
        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            $email = trim((string) ($row['email'] ?? ''));
            $label = $name !== '' ? $name : ($email !== '' ? $email : ('Каса #' . (int) $row['id']));

            $result[] = [
                'id'      => (int) $row['id'],
                'label'   => $label,
                'code'    => (string) $row['currencyCode'],
                'balance' => (int) $row['balance'],
            ];
        }

        return $result;
    }
}
