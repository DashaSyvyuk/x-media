<?php

namespace App\Repository;

use App\Entity\Warranty;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Warranty|null find($id, $lockMode = null, $lockVersion = null)
 * @method Warranty|null findOneBy(array $criteria, array $orderBy = null)
 * @method Warranty[]    findAll()
 * @method Warranty[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WarrantyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Warranty::class);
    }
}
