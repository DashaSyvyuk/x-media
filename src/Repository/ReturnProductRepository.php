<?php

namespace App\Repository;

use App\Entity\ReturnProduct;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReturnProduct>
 */
class ReturnProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReturnProduct::class);
    }

    public function createAdminListQueryBuilder(
        string $search = '',
        ?string $status = null,
        string $sort = 'id',
        string $direction = 'DESC',
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.product', 'product')->addSelect('product')
            ->leftJoin('r.supplier', 'supplier')->addSelect('supplier');

        $search = trim($search);
        if ($search !== '') {
            $qb->andWhere(
                'LOWER(r.name) LIKE :search
                OR LOWER(r.surname) LIKE :search
                OR LOWER(r.phone) LIKE :search
                OR LOWER(r.email) LIKE :search',
            )->setParameter('search', '%' . mb_strtolower($search) . '%');
        }

        if ($status !== null && $status !== '') {
            $qb->andWhere('r.status = :status')->setParameter('status', $status);
        }

        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
        $allowedSorts = ['id', 'name', 'phone', 'status', 'amount', 'createdAt'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'id';
        }

        $qb->orderBy('r.' . $sort, $direction);

        return $qb;
    }

    /**
     * @return array{total: int, new: int, processing: int, completed: int, refused: int}
     */
    public function getStatusSummary(?string $statusFilter = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->select('r.status', 'COUNT(r.id) AS cnt');

        if ($statusFilter !== null && $statusFilter !== '') {
            $qb->where('r.status = :status')->setParameter('status', $statusFilter);
        }

        $rows = $qb->groupBy('r.status')->getQuery()->getScalarResult();

        $summary = [
            'total'      => 0,
            'new'        => 0,
            'processing' => 0,
            'completed'  => 0,
            'refused'    => 0,
        ];

        foreach ($rows as $row) {
            $count = (int) $row['cnt'];
            $summary['total'] += $count;

            match ($row['status']) {
                ReturnProduct::STATUS_NEW        => $summary['new'] += $count,
                ReturnProduct::STATUS_PROCESSING => $summary['processing'] += $count,
                ReturnProduct::STATUS_COMPLETED  => $summary['completed'] += $count,
                ReturnProduct::STATUS_REFUSED    => $summary['refused'] += $count,
                default                          => null,
            };
        }

        return $summary;
    }
}
