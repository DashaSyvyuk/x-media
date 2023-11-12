<?php

namespace App\Repository;

use App\Entity\PromotionProduct;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PromotionProduct|null find($id, $lockMode = null, $lockVersion = null)
 * @method PromotionProduct|null findOneBy(array $criteria, array $orderBy = null)
 * @method PromotionProduct[]    findAll()
 * @method PromotionProduct[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PromotionProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PromotionProduct::class);
    }
}
