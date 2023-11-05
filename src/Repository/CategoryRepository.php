<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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

    public function getCategoriesTree(int $parent = null): array
    {
        $result = [];
        $query = $this->createQueryBuilder('c');

        if ($parent) {
            $query = $query
                ->andWhere('c.parent = :parent')
                ->setParameter('parent', $parent);
        } else {
            $query = $query->andWhere('c.parent is NULL');
        }

        $categories = $query->andWhere('c.status = :status')
            ->setParameter('status', Category::ACTIVE)
            ->orderBy('c.position', 'ASC')
            ->getQuery()
            ->getResult();

        foreach ($categories as $category) {
            $categoryArray['id'] = $category->getId();
            $categoryArray['title'] = $category->getTitle();
            $categoryArray['image'] = $category->getImage();
            $categoryArray['slug'] = $category->getSlug();
            $categoryArray['showInHeader'] = $category->getShowInHeader();
            $categoryArray['children'] = $this->getCategoriesTree($category->getId());
            $categoryArray['productsCount'] = $category->getProducts()->count();
            $result[] = $categoryArray;
        }

        return $result;
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

    public function getCategoriesIdsWithoutChildren(): array
    {
        $categories = $this->getCategoriesTree();

        return $this->getCategoriesId($categories);
    }

    private function getCategoriesId(array $categories): array
    {
        $result = [];
        foreach ($categories as $category) {
            if (empty($category['children'])) {
                $result[] = $category['id'];
            } else {
                $result = array_merge($result, $this->getCategoriesId($category['children']));
            }
        }

        return $result;
    }
}
