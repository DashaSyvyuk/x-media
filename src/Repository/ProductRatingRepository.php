<?php

namespace App\Repository;

use App\Entity\Product;
use App\Entity\ProductRating;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductRating>
 */
class ProductRatingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductRating::class);
    }

    /**
     * @param array<string, string|Product> $data
     */
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
