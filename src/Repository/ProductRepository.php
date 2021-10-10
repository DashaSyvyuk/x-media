<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 * @method Product|null findOneBy(array $criteria, array $orderBy = null)
 * @method Product[]    findAll()
 * @method Product[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function findByCategoryAndAttributes(Category $category, array $attributes)
    {
        $query = $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'category')
            ->andWhere('category = :category')
            ->setParameter('category', $category);

        if ($attributes) {
            $query
                ->leftJoin('p.filterAttributes', 'productFilterAttribute')
                ->leftJoin('productFilterAttribute.filterAttribute', 'filterAttribute')
                ->andWhere('filterAttribute.id IN (:ids)')
                ->setParameter('ids', $attributes);
        }

        return $query;
    }
}
