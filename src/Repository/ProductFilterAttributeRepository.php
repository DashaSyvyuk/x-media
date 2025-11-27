<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\ProductFilterAttribute;
use App\Entity\Promotion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductFilterAttribute>
 */
class ProductFilterAttributeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductFilterAttribute::class);
    }

    /**
     * @return array<int, string>
     */
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

    /**
     * @return array<int, string>
     */
    public function findFilterParameterByTitleAndSearchString(string $title, string $search, ?Category $category): array
    {
        $result = [];

        [$titleQuery, $codeQuery] = $this->prepareSearchString($search);

        $query = $this->createQueryBuilder('pfa')
            ->leftJoin('pfa.filter', 'f')
            ->leftJoin('pfa.product', 'product')
            ->orWhere($titleQuery)
            ->orWhere($codeQuery)
            ->andWhere('product.status = :productStatus')
            ->setParameter('productStatus', Product::STATUS_ACTIVE)
        ;

        if ($category) {
            $query = $query
                ->leftJoin('f.category', 'category')
                ->andWhere('category = :category')
                ->andWhere('category.status = :categoryStatus')
                ->setParameter('category', $category)
                ->setParameter('categoryStatus', Category::ACTIVE)
            ;
        }

        $productFilterAttributes = $query
            ->andWhere('f.title = :title')
            ->setParameter('title', $title)
            ->getQuery()
            ->getResult()
        ;

        foreach ($productFilterAttributes as $attribute) {
            $result[] = $attribute->getFilterAttribute()->getValue();
        }

        return array_unique($result);
    }

    /**
     * @return string[]
     */
    private function prepareSearchString(string $search): array
    {
        $words = explode(' ', trim($search));
        $titleConditions = [];
        $codeConditions = [];

        foreach ($words as $word) {
            $wordEscaped = str_replace("'", "''", trim($word));
            $titleConditions[] = "product.title LIKE '%" . $wordEscaped . "%'";
            $codeConditions[] = "product.productCode LIKE '%" . $wordEscaped . "%'";
        }

        $titleQuery = '(' . implode(' AND ', $titleConditions) . ')';
        $codeQuery = '(' . implode(' AND ', $codeConditions) . ')';

        return [$titleQuery, $codeQuery];
    }
}
