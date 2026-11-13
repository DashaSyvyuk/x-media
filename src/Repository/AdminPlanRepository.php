<?php

namespace App\Repository;

use App\Entity\AdminPlan;
use App\Entity\AdminPlanBoard;
use App\Entity\AdminPlanColumn;
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
    public function findForBoard(AdminPlanBoard $board): array
    {
        /** @var list<AdminPlan> $plans */
        $plans = $this->createQueryBuilder('p')
            ->leftJoin('p.assignee', 'a')->addSelect('a')
            ->leftJoin('p.planColumn', 'c')->addSelect('c')
            ->andWhere('p.board = :board')
            ->setParameter('board', $board)
            ->orderBy('c.position', 'ASC')
            ->addOrderBy('p.position', 'ASC')
            ->addOrderBy('p.id', 'ASC')
            ->getQuery()
            ->getResult();

        return $plans;
    }

    public function getNextPosition(AdminPlanColumn $column): int
    {
        $max = $this->createQueryBuilder('p')
            ->select('MAX(p.position)')
            ->andWhere('p.planColumn = :column')
            ->setParameter('column', $column)
            ->getQuery()
            ->getSingleScalarResult();

        return ((int) $max) + 1;
    }

    public function countForColumn(AdminPlanColumn $column): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.planColumn = :column')
            ->setParameter('column', $column)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Dated incomplete plans for the dashboard planning block.
     *
     * @return list<AdminPlan>
     */
    public function findDashboardPlans(int $limit = 50): array
    {
        /** @var list<AdminPlan> $plans */
        $plans = $this->createQueryBuilder('p')
            ->leftJoin('p.assignee', 'a')->addSelect('a')
            ->leftJoin('p.board', 'b')->addSelect('b')
            ->leftJoin('p.planColumn', 'c')->addSelect('c')
            ->andWhere('p.scheduledDate IS NOT NULL')
            ->andWhere('c.isDone = false')
            ->orderBy('p.scheduledDate', 'ASC')
            ->addOrderBy('p.position', 'ASC')
            ->addOrderBy('p.id', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $plans;
    }

    /**
     * @deprecated Use findDashboardPlans()
     *
     * @return list<AdminPlan>
     */
    public function findTodayPending(\DateTimeInterface $today, int $limit = 50): array
    {
        return $this->findDashboardPlans($limit);
    }

    public function countDashboardPending(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->leftJoin('p.planColumn', 'c')
            ->andWhere('p.scheduledDate IS NOT NULL')
            ->andWhere('c.isDone = false')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @deprecated Use countDashboardPending()
     */
    public function countTodayPending(\DateTimeInterface $today): int
    {
        return $this->countDashboardPending();
    }

    /**
     * @return list<AdminPlan>
     */
    public function findNeighbors(AdminPlan $plan): array
    {
        /** @var list<AdminPlan> $plans */
        $plans = $this->createQueryBuilder('p')
            ->andWhere('p.planColumn = :column')
            ->setParameter('column', $plan->getPlanColumn())
            ->orderBy('p.position', 'ASC')
            ->addOrderBy('p.id', 'ASC')
            ->getQuery()
            ->getResult();

        return $plans;
    }
}
