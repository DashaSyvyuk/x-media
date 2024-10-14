<?php

namespace App\Repository;

use App\Entity\CategoryFeedPrice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CategoryFeedPrice|null find($id, $lockMode = null, $lockVersion = null)
 * @method CategoryFeedPrice|null findOneBy(array $criteria, array $orderBy = null)
 * @method CategoryFeedPrice[]    findAll()
 * @method CategoryFeedPrice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoryFeedPriceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CategoryFeedPrice::class);
    }
}
