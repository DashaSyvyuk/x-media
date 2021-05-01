<?php

namespace App\Repository;

use App\Entity\FilterParameterValue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method FilterParameterValue|null find($id, $lockMode = null, $lockVersion = null)
 * @method FilterParameterValue|null findOneBy(array $criteria, array $orderBy = null)
 * @method FilterParameterValue[]    findAll()
 * @method FilterParameterValue[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FilterParameterValueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FilterParameterValue::class);
    }
}
