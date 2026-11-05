<?php

namespace App\Repository;

use App\Entity\AdminPlanBoard;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdminPlanBoard>
 */
class AdminPlanBoardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdminPlanBoard::class);
    }

    /**
     * @return list<AdminPlanBoard>
     */
    public function findOrdered(): array
    {
        /** @var list<AdminPlanBoard> $boards */
        $boards = $this->createQueryBuilder('b')
            ->orderBy('b.position', 'ASC')
            ->addOrderBy('b.id', 'ASC')
            ->getQuery()
            ->getResult();

        return $boards;
    }

    public function getNextPosition(): int
    {
        $max = $this->createQueryBuilder('b')
            ->select('MAX(b.position)')
            ->getQuery()
            ->getSingleScalarResult();

        return ((int) $max) + 1;
    }

    public function ensureDefaultBoard(): AdminPlanBoard
    {
        $existing = $this->findOrdered();
        if ($existing !== []) {
            return $existing[0];
        }

        $board = (new AdminPlanBoard())
            ->setTitle('Загальне')
            ->setColor('teal')
            ->setPosition(0);

        $this->getEntityManager()->persist($board);
        $this->getEntityManager()->flush();

        return $board;
    }
}
