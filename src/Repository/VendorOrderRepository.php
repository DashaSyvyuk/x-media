<?php

namespace App\Repository;

use App\Entity\VendorOrder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VendorOrder>
 */
class VendorOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VendorOrder::class);
    }

    public function createAdminListQueryBuilder(
        string $search = '',
        ?string $status = null,
        ?int $supplierId = null,
        string $sort = 'id',
        string $direction = 'DESC',
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('v')
            ->leftJoin('v.supplier', 'supplier')->addSelect('supplier');

        $search = trim($search);
        if ($search !== '') {
            $qb->andWhere(
                'LOWER(v.supplierOrderNumber) LIKE :search OR LOWER(v.productTitle) LIKE :search '
                . 'OR LOWER(supplier.title) LIKE :search',
            )->setParameter('search', '%' . mb_strtolower($search) . '%');
        }

        if ($status !== null && $status !== '') {
            $qb->andWhere('v.status = :status')->setParameter('status', $status);
        }

        if ($supplierId !== null && $supplierId > 0) {
            $qb->andWhere('supplier.id = :supplierId')->setParameter('supplierId', $supplierId);
        }

        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
        $allowedSorts = ['id', 'supplierOrderNumber', 'productTitle', 'price', 'status', 'createdAt'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'id';
        }

        $qb->orderBy('v.' . $sort, $direction);

        return $qb;
    }

    /**
     * @return VendorOrder[]
     */
    public function findActiveForBoard(int $limit = 100): array
    {
        return $this->createQueryBuilder('v')
            ->leftJoin('v.supplier', 'supplier')->addSelect('supplier')
            ->leftJoin('v.items', 'items')->addSelect('items')
            ->where('v.status IN (:statuses)')
            ->setParameter('statuses', VendorOrder::BOARD_STATUSES)
            ->orderBy('v.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
