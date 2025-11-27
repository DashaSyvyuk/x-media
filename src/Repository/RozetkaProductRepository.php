<?php

namespace App\Repository;

use App\Entity\RozetkaProduct;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RozetkaProduct>
 */
class RozetkaProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RozetkaProduct::class);
    }

    public function create(RozetkaProduct $rozetkaProduct): RozetkaProduct
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($rozetkaProduct);
        $entityManager->flush();

        return $rozetkaProduct;
    }

    public function update(RozetkaProduct $rozetkaProduct): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->refresh($rozetkaProduct);
        $entityManager->flush();
    }
}
