<?php

namespace App\Repository;

use App\Entity\Card;
use App\Entity\CardOperation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Card>
 */
class CardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Card::class);
    }

    /**
     * @return list<array{card: Card, activeCount: int}>
     */
    public function findAllWithActiveOperationCounts(): array
    {
        /** @var Card[] $cards */
        $cards = $this->createQueryBuilder('c')
            ->orderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult();

        if ($cards === []) {
            return [];
        }

        $ids = array_map(static fn (Card $card): int => $card->getId(), $cards);

        /** @var list<array{cardId: int, activeCount: string}> $rows */
        $rows = $this->getEntityManager()->createQueryBuilder()
            ->select('IDENTITY(o.card) AS cardId, COUNT(o.id) AS activeCount')
            ->from(CardOperation::class, 'o')
            ->where('o.card IN (:ids)')
            ->andWhere('o.isDone = false')
            ->setParameter('ids', $ids)
            ->groupBy('o.card')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row['cardId']] = (int) $row['activeCount'];
        }

        $result = [];
        foreach ($cards as $card) {
            $result[] = [
                'card'        => $card,
                'activeCount' => $counts[$card->getId()] ?? 0,
            ];
        }

        return $result;
    }
}
