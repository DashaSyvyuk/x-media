<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Category|null find($id, $lockMode = null, $lockVersion = null)
 * @method Category|null findOneBy(array $criteria, array $orderBy = null)
 * @method Category[]    findAll()
 * @method Category[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    public function getCategoriesForProducts(array $ids, bool $withActiveProducts = true): array
    {
        $result = [];
        $query = $this->createQueryBuilder('c')
            ->leftJoin('c.products', 'p')
            ->where('p.id IN (:ids)')
            ->andWhere('c.status = :status')
            ->andWhere('c.hotlineCategory IS NOT NULL')
            ->andWhere('c.promCategoryLink IS NOT NULL')
            ->setParameter('ids', $ids)
            ->setParameter('status', 'ACTIVE');

        if ($withActiveProducts) {
            $query = $query
                ->andWhere('p.status = :product_status')
                ->setParameter('product_status', Product::STATUS_ACTIVE);
        }
        $categories = $query
            ->orderBy('c.title', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        foreach ($categories as $category) {
            $categoryArray['id'] = $category->getId();
            $categoryArray['title'] = $category->getHotlineCategory();
            $categoryArray['promCategoryLink'] = $category->getPromCategoryLink();
            $result[] = $categoryArray;
        }

        return $result;
    }
}
