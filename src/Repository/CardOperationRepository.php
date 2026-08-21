<?php

namespace App\Repository;

use App\Entity\Card;
use App\Entity\CardOperation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CardOperation>
 */
class CardOperationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CardOperation::class);
    }

    /**
     * Returns all operations for a card grouped by year-month key (YYYY-MM).
     * Only months from the earliest active operation up to current month are returned.
     *
     * @return array<string, CardOperation[]>  key = 'YYYY-MM'
     */
    public function findGroupedByMonth(Card $card): array
    {
        /** @var CardOperation[] $operations */
        $operations = $this->createQueryBuilder('o')
            ->where('o.card = :card')
            ->setParameter('card', $card)
            ->orderBy('o.operatedAt', 'DESC')
            ->getQuery()
            ->getResult();

        if ($operations === []) {
            return [];
        }

        $grouped = [];
        foreach ($operations as $op) {
            $key = $op->getOperatedAt()->format('Y-m');
            $grouped[$key][] = $op;
        }

        // Ensure months from earliest to current exist (even if empty).
        $earliest = min(array_keys($grouped));
        $current  = (new \DateTime())->format('Y-m');

        [$ey, $em] = explode('-', $earliest);
        [$cy, $cm] = explode('-', $current);

        $cursor = new \DateTime($earliest . '-01');
        $end    = new \DateTime($current . '-01');

        $result = [];
        while ($cursor <= $end) {
            $key = $cursor->format('Y-m');
            $result[$key] = $grouped[$key] ?? [];
            $cursor->modify('+1 month');
        }

        return array_reverse($result, true);
    }

    /** @return CardOperation[] */
    public function findAllOrderedDesc(): array
    {
        return $this->createQueryBuilder('o')
            ->join('o.card', 'c')
            ->addSelect('c')
            ->orderBy('o.operatedAt', 'DESC')
            ->addOrderBy('o.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
