<?php

namespace App\Repository;

use App\Entity\PlanningGoodBatch;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PlanningGoodBatch>
 */
class PlanningGoodBatchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlanningGoodBatch::class);
    }

    /**
     * @return list<PlanningGoodBatch>
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.goods', 'g')
            ->addSelect('g')
            ->leftJoin('g.warehouse', 'w')
            ->addSelect('w')
            ->orderBy('b.recordedDate', 'DESC')
            ->addOrderBy('b.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
