<?php

namespace App\Repository;

use App\Entity\SupplierOrder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SupplierOrder|null find($id, $lockMode = null, $lockVersion = null)
 * @method SupplierOrder|null findOneBy(array $criteria, array $orderBy = null)
 * @method SupplierOrder[]    findAll()
 * @method SupplierOrder[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SupplierOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SupplierOrder::class);
    }
}
