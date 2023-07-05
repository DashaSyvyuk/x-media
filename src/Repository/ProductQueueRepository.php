<?php

namespace App\Repository;

use App\Entity\ProductQueue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ProductQueue|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductQueue|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductQueue[]    findAll()
 * @method ProductQueue[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductQueueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductQueue::class);
    }
}
