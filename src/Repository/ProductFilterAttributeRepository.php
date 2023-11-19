<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\ProductFilterAttribute;
use App\Entity\Promotion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ProductFilterAttribute|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductFilterAttribute|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductFilterAttribute[]    findAll()
 * @method ProductFilterAttribute[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductFilterAttributeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductFilterAttribute::class);
    }

    public function findFilterParameterByTitle(string $title, Promotion $promotion, ?Category $category): array
    {
        $result = [];
        $query = $this->createQueryBuilder('pfa')
            ->leftJoin('pfa.filter', 'f')
            ->leftJoin('pfa.product', 'product')
            ->leftJoin('product.promotionProducts', 'pp');

        if ($category) {
            $query = $query
                ->leftJoin('f.category', 'category')
                ->andWhere('category = :category')
                ->setParameter('category', $category);
        }

        $productFilterAttributes = $query
            ->andWhere('f.title = :title')
            ->andWhere('pp.promotion = :promotion')
            ->setParameter('promotion', $promotion)
            ->setParameter('title', $title)
            ->getQuery()
            ->getResult()
        ;

        foreach ($productFilterAttributes as $attribute) {
            $result[] = $attribute->getFilterAttribute()->getValue();
        }

        return array_unique($result);
    }
}
