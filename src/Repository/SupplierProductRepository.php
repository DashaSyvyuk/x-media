<?php

namespace App\Repository;

use App\Entity\SupplierProduct;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SupplierProduct|null find($id, $lockMode = null, $lockVersion = null)
 * @method SupplierProduct|null findOneBy(array $criteria, array $orderBy = null)
 * @method SupplierProduct[]    findAll()
 * @method SupplierProduct[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SupplierProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SupplierProduct::class);
    }
}
