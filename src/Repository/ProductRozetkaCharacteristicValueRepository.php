<?php

namespace App\Repository;

use App\Entity\ProductRozetkaCharacteristicValue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ProductRozetkaCharacteristicValue|null find($id, $lockMode=null, $lockVersion=null)
 * @method ProductRozetkaCharacteristicValue|null findOneBy(array $criteria, array $orderBy=null)
 * @method ProductRozetkaCharacteristicValue[] findAll()
 * @method ProductRozetkaCharacteristicValue[] findBy(array $criteria, array $orderBy=null, $limit=null, $offset=null)
 */
class ProductRozetkaCharacteristicValueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductRozetkaCharacteristicValue::class);
    }
}
