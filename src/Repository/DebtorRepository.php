<?php

namespace App\Repository;

use App\Entity\Debtor;
use App\Entity\DebtorPayment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Debtor>
 */
class DebtorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Debtor::class);
    }

    public function createAdminListQueryBuilder(
        string $search = '',
        ?bool $active = null,
        string $sort = 'total',
        string $direction = 'DESC',
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.currency', 'currency')
            ->addSelect('currency');

        $search = trim($search);
        if ($search !== '') {
            $qb->andWhere('LOWER(d.name) LIKE :search')
                ->setParameter('search', '%' . mb_strtolower($search) . '%');
        }

        if ($active !== null) {
            $qb->andWhere('d.active = :active')->setParameter('active', $active);
        }

        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';

        if ($sort === 'total') {
            $qb->addSelect(
                '(SELECT COALESCE(SUM(p.sum), 0) FROM ' . DebtorPayment::class . ' p WHERE p.debtor = d) AS HIDDEN balanceSort',
            )->orderBy('balanceSort', $direction);
        } elseif ($sort === 'currency') {
            $qb->orderBy('currency.title', $direction);
        } else {
            $allowedSorts = ['id', 'name', 'active', 'createdAt'];
            if (! in_array($sort, $allowedSorts, true)) {
                $sort = 'name';
            }
            $qb->orderBy('d.' . $sort, $direction);
        }

        return $qb;
    }

    /**
     * @param array<int, int> $debtorIds
     *
     * @return array<int, int>
     */
    public function getPaymentTotalsByDebtorIds(array $debtorIds): array
    {
        if ($debtorIds === []) {
            return [];
        }

        $rows = $this->getEntityManager()->createQueryBuilder()
            ->select('IDENTITY(p.debtor) AS debtorId', 'COALESCE(SUM(p.sum), 0) AS total')
            ->from(DebtorPayment::class, 'p')
            ->where('p.debtor IN (:ids)')
            ->groupBy('p.debtor')
            ->setParameter('ids', $debtorIds)
            ->getQuery()
            ->getScalarResult();

        $totals = [];
        foreach ($rows as $row) {
            $totals[(int) $row['debtorId']] = (int) $row['total'];
        }

        return $totals;
    }

    /**
     * @return array{
     *     contacts: int,
     *     owedToYouByCurrency: list<array{code: string, total: int}>,
     *     youOweByCurrency: list<array{code: string, total: int}>
     * }
     */
    public function getFinanceSummary(?bool $active = null): array
    {
        $qb = $this->createQueryBuilder('d')
            ->select(
                'c.id AS currencyId',
                'c.code AS currencyCode',
                '(SELECT COALESCE(SUM(p.sum), 0) FROM ' . DebtorPayment::class . ' p WHERE p.debtor = d) AS balance',
            )
            ->join('d.currency', 'c');

        if ($active !== null) {
            $qb->andWhere('d.active = :active')->setParameter('active', $active);
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
                    $owedToYouByCurrency[$currencyId] = [
                        'code'  => $code,
                        'total' => 0,
                    ];
                }
                $owedToYouByCurrency[$currencyId]['total'] += $balance;
            } elseif ($balance < 0) {
                if (! isset($youOweByCurrency[$currencyId])) {
                    $youOweByCurrency[$currencyId] = [
                        'code'  => $code,
                        'total' => 0,
                    ];
                }
                $youOweByCurrency[$currencyId]['total'] += abs($balance);
            }
        }

        $sortByCode = static function (array $a, array $b): int {
            return strcmp($a['code'], $b['code']);
        };

        $owedList = array_values($owedToYouByCurrency);
        $oweList  = array_values($youOweByCurrency);
        usort($owedList, $sortByCode);
        usort($oweList, $sortByCode);

        $contactsQb = $this->createQueryBuilder('d')->select('COUNT(d.id)');
        if ($active !== null) {
            $contactsQb->andWhere('d.active = :active')->setParameter('active', $active);
        }

        return [
            'contacts'            => (int) $contactsQb->getQuery()->getSingleScalarResult(),
            'owedToYouByCurrency' => $owedList,
            'youOweByCurrency'    => $oweList,
        ];
    }
}
