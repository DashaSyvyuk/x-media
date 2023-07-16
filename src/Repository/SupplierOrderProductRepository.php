<?php

namespace App\Repository;

use App\Entity\SupplierOrderProduct;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SupplierOrderProduct|null find($id, $lockMode = null, $lockVersion = null)
 * @method SupplierOrderProduct|null findOneBy(array $criteria, array $orderBy = null)
 * @method SupplierOrderProduct[]    findAll()
 * @method SupplierOrderProduct[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SupplierOrderProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SupplierOrderProduct::class);
    }
}
