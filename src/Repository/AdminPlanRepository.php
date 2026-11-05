<?php

namespace App\Repository;

use App\Entity\AdminPlan;
use App\Entity\AdminPlanBoard;
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
            ->andWhere('p.board = :board')
            ->setParameter('board', $board)
            ->orderBy('p.status', 'ASC')
            ->addOrderBy('p.position', 'ASC')
            ->addOrderBy('p.id', 'ASC')
            ->getQuery()
            ->getResult();

        return $plans;
    }

    public function getNextPosition(AdminPlanBoard $board, string $status): int
    {
        $max = $this->createQueryBuilder('p')
            ->select('MAX(p.position)')
            ->andWhere('p.board = :board')
            ->andWhere('p.status = :status')
            ->setParameter('board', $board)
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();

        return ((int) $max) + 1;
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
            ->andWhere('p.scheduledDate IS NOT NULL')
            ->andWhere('p.status != :completed')
            ->setParameter('completed', AdminPlan::STATUS_COMPLETED)
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
            ->andWhere('p.scheduledDate IS NOT NULL')
            ->andWhere('p.status != :completed')
            ->setParameter('completed', AdminPlan::STATUS_COMPLETED)
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
            ->andWhere('p.board = :board')
            ->andWhere('p.status = :status')
            ->setParameter('board', $plan->getBoard())
            ->setParameter('status', $plan->getStatus())
            ->orderBy('p.position', 'ASC')
            ->addOrderBy('p.id', 'ASC')
            ->getQuery()
            ->getResult();

        return $plans;
    }
}
