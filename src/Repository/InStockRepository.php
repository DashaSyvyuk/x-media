<?php

namespace App\Repository;

use App\Entity\InStock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method InStock|null find($id, $lockMode = null, $lockVersion = null)
 * @method InStock|null findOneBy(array $criteria, array $orderBy = null)
 * @method InStock[]    findAll()
 * @method InStock[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InStockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InStock::class);
    }
}
