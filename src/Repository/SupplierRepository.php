<?php

namespace App\Repository;

use App\Entity\Supplier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Supplier>
 */
class SupplierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Supplier::class);
    }

    public function createAdminListQueryBuilder(
        string $search = '',
        ?bool $active = null,
        string $sort = 'title',
        string $direction = 'ASC',
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.currency', 'currency')->addSelect('currency');

        $search = trim($search);
        if ($search !== '') {
            $qb->andWhere(
                'LOWER(s.title) LIKE :search OR LOWER(s.name) LIKE :search OR LOWER(s.surname) LIKE :search OR LOWER(s.email) LIKE :search',
            )->setParameter('search', '%' . mb_strtolower($search) . '%');
        }

        if ($active !== null) {
            $qb->andWhere('s.active = :active')->setParameter('active', $active);
        }

        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $allowedSorts = ['id', 'title', 'name', 'phone', 'orderStorageDays', 'active'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'title';
        }

        $qb->orderBy('s.' . $sort, $direction);

        return $qb;
    }
}
