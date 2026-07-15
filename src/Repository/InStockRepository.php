<?php

namespace App\Repository;

use App\Entity\InStock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<InStock>
 */
class InStockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InStock::class);
    }

    public function createAdminListQueryBuilder(
        string $search = '',
        ?int $warehouseId = null,
        string $sort = 'warehouse',
        string $direction = 'ASC',
    ): \Doctrine\ORM\QueryBuilder {
        $qb = $this->createQueryBuilder('stock')
            ->leftJoin('stock.product', 'product')
            ->addSelect('product')
            ->leftJoin('stock.warehouse', 'warehouse')
            ->addSelect('warehouse');

        $search = ltrim(trim($search), '#');
        if ($search !== '') {
            $orX = $qb->expr()->orX();
            $orX->add($qb->expr()->like('LOWER(product.title)', ':searchText'));
            $orX->add($qb->expr()->like('LOWER(product.productCode)', ':searchText'));
            $orX->add($qb->expr()->like('LOWER(warehouse.title)', ':searchText'));
            $orX->add($qb->expr()->like('LOWER(warehouse.city)', ':searchText'));
            $qb->setParameter('searchText', '%' . mb_strtolower($search) . '%');

            if (ctype_digit($search)) {
                $searchId = (int) $search;
                $orX->add($qb->expr()->eq('product.id', ':searchProductId'));
                $orX->add($qb->expr()->eq('stock.id', ':searchStockId'));
                $qb->setParameter('searchProductId', $searchId);
                $qb->setParameter('searchStockId', $searchId);
            }

            $qb->andWhere($orX);
        }

        if ($warehouseId !== null && $warehouseId > 0) {
            $qb->andWhere('warehouse.id = :warehouseId')
                ->setParameter('warehouseId', $warehouseId);
        }

        $allowedSorts = [
            'id'        => 'stock.id',
            'product'   => 'product.title',
            'warehouse' => 'warehouse.title',
            'quantity'  => 'stock.quantity',
            'price'     => 'stock.purchasePrice',
        ];
        if (! isset($allowedSorts[$sort])) {
            $sort = 'warehouse';
        }
        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';

        return $qb->orderBy($allowedSorts[$sort], $direction);
    }
}
