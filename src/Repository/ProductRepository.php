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

    public function findByCategoryAndAttributes(
        Category $category,
        array $attributes,
        ?string $order,
        ?string $direction,
        ?int $priceFrom,
        ?int $priceTo
    ) {
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

        if ($priceFrom) {
            $query
                ->andWhere('p.price >= :from')
                ->setParameter('from', $priceFrom);
        }

        if ($priceTo) {
            $query
                ->andWhere('p.price <= :to')
                ->setParameter('to', $priceTo);
        }

        if ($order && $direction) {
            $query->orderBy('p.' . $order, $direction);
        }

        return $query;
    }

    public function findBySearch(string $search)
    {
        $search = explode(' ', $search);

        $query = $this->createQueryBuilder('p');

        foreach ($search as $value) {
            $query
                ->orWhere('p.title LIKE :title')
                ->orWhere('p.description LIKE :description')
                ->setParameter('title', '%' . $value . '%')
                ->setParameter('description', '%' . $value . '%');
        }

        return $query;
    }
}
