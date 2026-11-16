<?php

namespace App\Repository;

use App\Entity\InStock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('stock')
            ->leftJoin('stock.product', 'product')
            ->addSelect('product')
            ->leftJoin('stock.warehouse', 'warehouse')
            ->addSelect('warehouse');

        // Same #ID normalization as filters / product picker.
        $search = ltrim(trim($search), '#');
        if ($search !== '') {
            $words = preg_split('/\s+/u', $search) ?: [];
            $orX = $qb->expr()->orX();

            // Match products list: each word must appear (order-independent) per field.
            foreach (['title', 'productCode', 'productCode2'] as $field) {
                $andX = $qb->expr()->andX();
                foreach ($words as $k => $word) {
                    if ($word === '') {
                        continue;
                    }
                    $paramName = sprintf('%s_word%d', $field, $k);
                    $andX->add($qb->expr()->like("LOWER(product.$field)", ':' . $paramName));
                    $qb->setParameter($paramName, '%' . mb_strtolower($word) . '%');
                }
                if ($andX->count() > 0) {
                    $orX->add($andX);
                }
            }

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

            // Do not call distinct() here: there is no to-many join, and QB distinct()
            // combined with KNP distinct + ORDER BY joined fields can return empty pages.
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

        return $qb
            ->orderBy($allowedSorts[$sort], $direction)
            ->addOrderBy('stock.id', 'ASC');
    }
}
