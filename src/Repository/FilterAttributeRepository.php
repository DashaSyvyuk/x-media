<?php

namespace App\Repository;

use App\Entity\FilterAttribute;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method FilterAttribute|null find($id, $lockMode = null, $lockVersion = null)
 * @method FilterAttribute|null findOneBy(array $criteria, array $orderBy = null)
 * @method FilterAttribute[]    findAll()
 * @method FilterAttribute[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FilterAttributeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FilterAttribute::class);
    }
}
