<?php

namespace App\Repository;

use App\Entity\AdminPlan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdminPlan>
 */
class AdminPlanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdminPlan::class);
    }

    /**
     * @return list<AdminPlan>
     */
    public function findForBoard(bool $includeCompleted = false): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.assignee', 'a')->addSelect('a')
            ->leftJoin('p.createdBy', 'c')->addSelect('c')
            ->orderBy('p.scheduledDate', 'ASC')
            ->addOrderBy('p.id', 'ASC');

        if ($includeCompleted) {
            $qb->andWhere('p.completedAt IS NOT NULL');
        } else {
            $qb->andWhere('p.completedAt IS NULL');
        }

        /** @var list<AdminPlan> $plans */
        $plans = $qb->getQuery()->getResult();

        return $plans;
    }

    /**
     * @return list<AdminPlan>
     */
    public function findTodayPending(\DateTimeInterface $today, int $limit = 50): array
    {
        $day = \DateTime::createFromInterface($today)->setTime(0, 0);

        /** @var list<AdminPlan> $plans */
        $plans = $this->createQueryBuilder('p')
            ->leftJoin('p.assignee', 'a')->addSelect('a')
            ->andWhere('p.scheduledDate = :day')
            ->andWhere('p.completedAt IS NULL')
            ->setParameter('day', $day)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $plans;
    }

    public function countTodayPending(\DateTimeInterface $today): int
    {
        $day = \DateTime::createFromInterface($today)->setTime(0, 0);

        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.scheduledDate = :day')
            ->andWhere('p.completedAt IS NULL')
            ->setParameter('day', $day)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
