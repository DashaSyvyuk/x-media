<?php

namespace App\Repository;

use App\Entity\Warranty;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Warranty>
 */
class WarrantyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Warranty::class);
    }

    public function createAdminListQueryBuilder(
        string $search = '',
        ?string $status = null,
        string $sort = 'id',
        string $direction = 'DESC',
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('w')
            ->leftJoin('w.product', 'product')->addSelect('product')
            ->leftJoin('w.supplier', 'supplier')->addSelect('supplier');

        $search = trim($search);
        if ($search !== '') {
            $qb->andWhere(
                'LOWER(w.name) LIKE :search
                OR LOWER(w.surname) LIKE :search
                OR LOWER(w.phone) LIKE :search
                OR LOWER(w.email) LIKE :search
                OR LOWER(w.orderNumber) LIKE :search',
            )->setParameter('search', '%' . mb_strtolower($search) . '%');
        }

        if ($status !== null && $status !== '') {
            $qb->andWhere('w.status = :status')->setParameter('status', $status);
        }

        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
        $allowedSorts = ['id', 'name', 'phone', 'status', 'expenses', 'createdAt'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'id';
        }

        $qb->orderBy('w.' . $sort, $direction);

        return $qb;
    }

    /**
     * @return array{total: int, new: int, active: int, completed: int, failed: int}
     */
    public function getStatusSummary(?string $statusFilter = null): array
    {
        $qb = $this->createQueryBuilder('w')
            ->select('w.status', 'COUNT(w.id) AS cnt');

        if ($statusFilter !== null && $statusFilter !== '') {
            $qb->where('w.status = :status')->setParameter('status', $statusFilter);
        }

        $rows = $qb->groupBy('w.status')->getQuery()->getScalarResult();

        $summary = [
            'total'     => 0,
            'new'       => 0,
            'active'    => 0,
            'completed' => 0,
            'failed'    => 0,
        ];

        foreach ($rows as $row) {
            $count = (int) $row['cnt'];
            $summary['total'] += $count;

            match ($row['status']) {
                Warranty::STATUS_NEW                  => $summary['new'] += $count,
                Warranty::STATUS_COMPLETED            => $summary['completed'] += $count,
                Warranty::STATUS_NOT_FIXED,
                Warranty::STATUS_NOT_FIXED_RETURNED   => $summary['failed'] += $count,
                default                               => $summary['active'] += $count,
            };
        }

        return $summary;
    }
}
