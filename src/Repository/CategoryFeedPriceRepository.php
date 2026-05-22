<?php

namespace App\Repository;

use App\Entity\Feed;
use App\Entity\CategoryFeedPrice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CategoryFeedPrice>
 */
class CategoryFeedPriceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CategoryFeedPrice::class);
    }

    /**
     * @return array<int, CategoryFeedPrice>
     */
    public function findByFeedIndexedByCategoryId(Feed $feed): array
    {
        $rows = $this->createQueryBuilder('cfp')
            ->leftJoin('cfp.category', 'category')
            ->addSelect('category')
            ->andWhere('cfp.feed = :feed')
            ->setParameter('feed', $feed)
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($rows as $row) {
            $result[$row->getCategory()->getId()] = $row;
        }

        return $result;
    }
}
