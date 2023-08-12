<?php

namespace App\Repository;

use App\Entity\Category;
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
            ->getResult()
            ;

        foreach ($categories as $category) {
            $categoryArray['id'] = $category->getId();
            $categoryArray['title'] = $category->getTitle();
            $categoryArray['image'] = $category->getImage();
            $categoryArray['slug'] = $category->getSlug();
            $categoryArray['showInHeader'] = $category->getShowInHeader();
            $categoryArray['children'] = $this->getCategoriesTree($category->getId());
            $result[] = $categoryArray;
        }

        return $result;
    }
}
