<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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
    ): QueryBuilder
    {
        $query = $this->prepareQuery($category, $attributes, $priceFrom, $priceTo);

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

    private function prepareQuery(
        Category $category,
        array $attributes,
        ?int $priceFrom,
        ?int $priceTo
    ): QueryBuilder
    {
        $query = $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'category')
            ->andWhere('category = :category')
            ->andWhere('p.status = :status')
            ->setParameter('category', $category)
            ->setParameter('status', Product::STATUS_ACTIVE);

        if ($attributes) {
            foreach ($attributes as $filter => $values) {
                $query
                    ->leftJoin('p.filterAttributes', sprintf('productFilterAttribute%d', $filter))
                    ->leftJoin(sprintf('productFilterAttribute%d.filterAttribute', $filter), sprintf('filterAttribute%d', $filter))
                    ->andWhere(sprintf('filterAttribute%d.id IN (:ids%d)', $filter, $filter))
                    ->setParameter(sprintf('ids%d', $filter), $values);
            }
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

        return $query;
    }

    public function getMinAndMaxPriceInCategory(
        Category $category,
        array $attributes,
        ?int $priceFrom,
        ?int $priceTo
    ): array
    {
        $query = $this->prepareQuery($category, $attributes, $priceFrom, $priceTo);

        $query->select('MIN(p.price) AS min_price, MAX(p.price) AS max_price');

        return $query->getQuery()->getArrayResult()[0];
    }
}
