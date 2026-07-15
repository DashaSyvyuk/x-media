<?php

namespace App\Repository;

use App\Entity\Warehouse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Warehouse>
 */
class WarehouseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Warehouse::class);
    }

    public function createAdminListQueryBuilder(
        string $search = '',
        ?bool $active = null,
        string $sort = 'title',
        string $direction = 'ASC',
    ): \Doctrine\ORM\QueryBuilder {
        $qb = $this->createQueryBuilder('w')
            ->leftJoin('w.adminUser', 'adminUser')->addSelect('adminUser');

        $search = trim($search);
        if ($search !== '') {
            $qb->andWhere(
                'LOWER(w.title) LIKE :search OR LOWER(w.city) LIKE :search OR LOWER(w.address) LIKE :search',
            )->setParameter('search', '%' . mb_strtolower($search) . '%');
        }

        if ($active !== null) {
            $qb->andWhere('w.active = :active')->setParameter('active', $active);
        }

        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $allowedSorts = ['id', 'title', 'city', 'active'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'title';
        }

        $qb->orderBy('w.' . $sort, $direction);

        return $qb;
    }

    /**
     * @param int[] $warehouseIds
     *
     * @return array<int, int>
     */
    public function getStockQuantitySumByWarehouseIds(array $warehouseIds): array
    {
        if ($warehouseIds === []) {
            return [];
        }

        $rows = $this->getEntityManager()->createQueryBuilder()
            ->select('IDENTITY(s.warehouse) AS warehouseId', 'COALESCE(SUM(s.quantity), 0) AS qty')
            ->from(\App\Entity\InStock::class, 's')
            ->where('s.warehouse IN (:ids)')
            ->setParameter('ids', $warehouseIds)
            ->groupBy('s.warehouse')
            ->getQuery()
            ->getScalarResult();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['warehouseId']] = (int) $row['qty'];
        }

        return $result;
    }
}
