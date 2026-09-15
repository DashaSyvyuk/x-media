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

        $search = ltrim(trim($search), '#');
        if ($search !== '') {
            // Join items only for filtering — do not select them (breaks KNP distinct pages).
            $qb->leftJoin('v.items', 'items');

            $orX = $qb->expr()->orX(
                $qb->expr()->like('LOWER(v.supplierOrderNumber)', ':searchText'),
                $qb->expr()->like('LOWER(v.productTitle)', ':searchText'),
                $qb->expr()->like('LOWER(supplier.title)', ':searchText'),
                $qb->expr()->like('LOWER(items.title)', ':searchText'),
            );

            if (ctype_digit($search)) {
                $orX->add($qb->expr()->eq('v.id', ':searchId'));
                $qb->setParameter('searchId', (int) $search);
            }

            $qb->andWhere($orX)
                ->setParameter('searchText', '%' . mb_strtolower($search) . '%')
                ->distinct();
        }

        if ($status !== null && $status !== '') {
            $qb->andWhere('v.status = :status')->setParameter('status', $status);
        }

        if ($supplierId !== null && $supplierId > 0) {
            $qb->andWhere('supplier.id = :supplierId')->setParameter('supplierId', $supplierId);
        }

        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
        $allowedSorts = [
            'id'                  => 'v.id',
            'supplierOrderNumber' => 'v.supplierOrderNumber',
            'productTitle'        => 'v.productTitle',
            'price'               => 'v.price',
            'status'              => 'v.status',
            'createdAt'           => 'v.createdAt',
        ];
        if (! isset($allowedSorts[$sort])) {
            $sort = 'id';
        }

        // Always use aliased fields — bare "id" breaks KnpPaginator ("no component field [id]").
        $qb->orderBy($allowedSorts[$sort], $direction);

        return $qb;
    }

    /**
     * Active supplier orders for the fulfillment board (new + waiting pickup).
     * No limit: these statuses must always stay visible.
     *
     * @return VendorOrder[]
     */
    public function findActiveForBoard(): array
    {
        return $this->createQueryBuilder('v')
            ->leftJoin('v.supplier', 'supplier')->addSelect('supplier')
            ->leftJoin('v.items', 'items')->addSelect('items')
            ->where('v.status IN (:statuses)')
            ->setParameter('statuses', VendorOrder::BOARD_STATUSES)
            ->orderBy('v.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
