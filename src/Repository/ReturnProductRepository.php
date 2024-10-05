<?php

namespace App\Repository;

use App\Entity\ReturnProduct;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ReturnProduct|null find($id, $lockMode = null, $lockVersion = null)
 * @method ReturnProduct|null findOneBy(array $criteria, array $orderBy = null)
 * @method ReturnProduct[]    findAll()
 * @method ReturnProduct[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReturnProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReturnProduct::class);
    }
}
