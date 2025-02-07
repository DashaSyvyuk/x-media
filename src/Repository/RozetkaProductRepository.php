<?php

namespace App\Repository;

use App\Entity\RozetkaProduct;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RozetkaProduct|null find($id, $lockMode = null, $lockVersion = null)
 * @method RozetkaProduct|null findOneBy(array $criteria, array $orderBy = null)
 * @method RozetkaProduct[]    findAll()
 * @method RozetkaProduct[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
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

    public function update(RozetkaProduct $rozetkaProduct)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->merge($rozetkaProduct);
        $entityManager->flush();
    }
}
