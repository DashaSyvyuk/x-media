<?php

namespace App\Repository;

use App\Entity\ProductRating;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ProductRating|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductRating|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductRating[]    findAll()
 * @method ProductRating[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductRatingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductRating::class);
    }

    public function create(array $data): ProductRating
    {
        $productRating = new ProductRating();
        $productRating->setProduct($data['product']);
        $productRating->setValue($data['rating']);

        $entityManager = $this->getEntityManager();
        $entityManager->persist($productRating);
        $entityManager->flush();

        return $productRating;
    }
}
