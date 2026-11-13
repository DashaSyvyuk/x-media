<?php

namespace App\Repository;

use App\Entity\AdminPlanBoard;
use App\Entity\AdminPlanColumn;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdminPlanColumn>
 */
class AdminPlanColumnRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdminPlanColumn::class);
    }

    /**
     * @return list<AdminPlanColumn>
     */
    public function findForBoard(AdminPlanBoard $board): array
    {
        /** @var list<AdminPlanColumn> $columns */
        $columns = $this->createQueryBuilder('c')
            ->andWhere('c.board = :board')
            ->setParameter('board', $board)
            ->orderBy('c.position', 'ASC')
            ->addOrderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult();

        return $columns;
    }

    public function getNextPosition(AdminPlanBoard $board): int
    {
        $max = $this->createQueryBuilder('c')
            ->select('MAX(c.position)')
            ->andWhere('c.board = :board')
            ->setParameter('board', $board)
            ->getQuery()
            ->getSingleScalarResult();

        return ((int) $max) + 1;
    }

    /**
     * @return list<AdminPlanColumn>
     */
    public function ensureDefaultsForBoard(AdminPlanBoard $board): array
    {
        $existing = $this->findForBoard($board);
        if ($existing !== []) {
            return $existing;
        }

        $em = $this->getEntityManager();
        $created = [];
        foreach (AdminPlanColumn::DEFAULTS as $index => $defaults) {
            $column = (new AdminPlanColumn())
                ->setBoard($board)
                ->setTitle($defaults['title'])
                ->setColor($defaults['color'])
                ->setIsDone($defaults['isDone'])
                ->setPosition($index);
            $em->persist($column);
            $created[] = $column;
        }
        $em->flush();

        return $created;
    }
}
