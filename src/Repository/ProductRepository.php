<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\Promotion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
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
        ?Category $category,
        array $attributes,
        ?string $search,
        ?string $order,
        ?string $direction,
        ?int $priceFrom,
        ?int $priceTo
    ): QueryBuilder
    {
        $query = $this->prepareQuery($category, $attributes, $search, $priceFrom, $priceTo);

        if ($order && $direction) {
            $query->orderBy('p.' . $order, $direction);
        }

        return $query;
    }

    private function prepareQuery(
        ?Category $category,
        array $attributes,
        ?string $search,
        ?int $priceFrom,
        ?int $priceTo
    ): QueryBuilder
    {
        $query = $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'category');

        if ($search) {
            [$titleQuery, $codeQuery] = $this->prepareSearchString($search);

            $query
                ->orWhere($titleQuery)
                ->orWhere($codeQuery);
        }

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

        $query
            ->andWhere('p.category = :category')
            ->andWhere('p.status = :status')
            ->setParameter('category', $category)
            ->setParameter('status', Product::STATUS_ACTIVE);

        return $query;
    }

    public function getMinAndMaxPriceInCategory(
        ?Category $category,
        array $attributes,
        ?string $search,
        ?int $priceFrom,
        ?int $priceTo
    ): array
    {
        $query = $this->prepareQuery($category, $attributes, $search, $priceFrom, $priceTo);

        $query->select('MIN(p.price) AS min_price, MAX(p.price) AS max_price');

        return $query->getQuery()->getArrayResult()[0];
    }

    public function getProductsForProm(): array
    {
        $result = [];
        $products = $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->andWhere('c.status = :status')
            ->andWhere('c.promCategoryLink IS NOT NULL')
            ->setParameter('status', 'ACTIVE')
            ->andWhere('p.status = :product_status')
            ->setParameter('product_status', Product::STATUS_ACTIVE)
            ->andWhere('c.showInPromFeed = :showInPromFeed')
            ->setParameter('showInPromFeed', true)
            ->orderBy('p.title', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        foreach ($products as $product) {
            $images = [];
            foreach ($product->getImages() as $item) {
                $images[] = 'https://x-media.com.ua/images/products/' . $item->getImageUrl();
            }

            $vendor = array_filter($product->getFilterAttributes()->toArray(), fn ($item) => in_array($item->getFilter()->getTitle(), ['Марка', 'Виробник']));
            $warranty = array_filter($product->getFilterAttributes()->toArray(), fn ($item) => $item->getFilter()->getTitle() == 'Гарантія');

            if (!empty($vendor)) {
                $row = [
                    'id' => $product->getId(),
                    'title' => strip_tags(addslashes($product->getTitle())),
                    'categoryId' => $product->getCategory()->getId(),
                    'price' => $product->getPrice(),
                    'images' => $images,
                    'characteristics' => $product->getCharacteristics(),
                    'description' => htmlentities($product->getDescription(), ENT_XML1),
                    'keywords' => addslashes($product->getMetaKeyword()),
                    'vendor' => $vendor[0]->getFilterAttribute()->getValue(),
                    'promCategoryLink' => $product->getCategory()->getPromCategoryLink(),
                    'article' => $product->getProductCode(),
                    'warranty' => $warranty ? $warranty[0]->getFilterAttribute()->getValue() : 12,
                ];

                $result[] = $row;
            }
        }

        return $result;
    }

    public function getProductsForHotline()
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->andWhere('c.status = :status')
            ->andWhere('c.hotlineCategory IS NOT NULL')
            ->setParameter('status', 'ACTIVE')
            ->andWhere('p.status = :product_status')
            ->setParameter('product_status', Product::STATUS_ACTIVE)
            ->andWhere('c.showInHotlineFeed = :showInHotlineFeed')
            ->setParameter('showInHotlineFeed', true)
            ->orderBy('p.title', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function getProductsForEkatalog()
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->andWhere('c.status = :status')
            ->setParameter('status', 'ACTIVE')
            ->andWhere('p.status = :product_status')
            ->setParameter('product_status', Product::STATUS_ACTIVE)
            ->andWhere('c.showInEkatalogFeed = :showInEkatalogFeed')
            ->setParameter('showInEkatalogFeed', true)
            ->orderBy('p.title', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }

    public function getProductsForRozetka()
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->andWhere('c.status = :status')
            ->andWhere('c.rozetkaCategory IS NOT NULL')
            ->setParameter('status', 'ACTIVE')
            ->andWhere('p.status = :product_status')
            ->setParameter('product_status', Product::STATUS_ACTIVE)
            ->andWhere('c.showInRozetkaFeed = :showInRozetkaFeed')
            ->setParameter('showInRozetkaFeed', true)
            ->orderBy('p.title', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }

    public function findByPromotionAndVendor(
        Promotion $promotion,
        ?Category $category,
        ?string $vendors,
        ?string $order,
        ?string $direction,
    ): QueryBuilder
    {
        $query = $this->createQueryBuilder('p')
            ->leftJoin('p.promotionProducts', 'pp')
            ->andWhere('pp.promotion = :promotion')
            ->andWhere('p.status = :status')
            ->setParameter('promotion', $promotion)
            ->setParameter('status', Product::STATUS_ACTIVE);

        if ($category) {
            $query
                ->leftJoin('p.category', 'category')
                ->andWhere('category = :category')
                ->setParameter('category', $category);
        }

        if ($vendors) {
            $query = $query
                ->leftJoin('p.filterAttributes', 'filterAttributes')
                ->leftJoin('filterAttributes.filterAttribute', 'filterAttribute')
                ->andWhere('filterAttribute.value IN (:vendors)')
                ->setParameter('vendors', explode(',', $vendors))
            ;
        }

        if ($order && $direction) {
            $query->orderBy('p.' . $order, $direction);
        }

        return $query;
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getCategoriesTreeForPromotion(array $categories, Promotion $promotion): array
    {
        $result = [];

        foreach ($categories as $category) {
            if (!empty($category['children'])) {
                $children = $this->getCategoriesTreeForPromotion($category['children'], $promotion);
                $total = array_sum(array_column($children, 'productsCount'));

                if ($total > 0) {
                    $result[] = array_merge($category, [
                        'productsCount' => $total,
                        'children' => $children,
                    ]);
                }
            } else {
                $productsCount = $this->createQueryBuilder('p')
                    ->select(['COUNT(p.id)'])
                    ->leftJoin('p.category', 'category')
                    ->leftJoin('p.promotionProducts', 'promotionProducts')
                    ->where('promotionProducts.promotion = :promotion')
                    ->andWhere('category.id = :id')
                    ->andWhere('p.status = :status')
                    ->setParameter('promotion', $promotion)
                    ->setParameter('id', $category['id'])
                    ->setParameter('status', Product::STATUS_ACTIVE)
                    ->getQuery()
                    ->getSingleScalarResult();

                if ($productsCount > 0) {
                    $result[] = array_merge($category, ['productsCount' => $productsCount]);
                }
            }
        }

        return $result;
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getCategoriesTreeForSearch(array $categories, string $search): array
    {
        $result = [];

        foreach ($categories as $category) {
            if (!empty($category['children'])) {
                $children = $this->getCategoriesTreeForSearch($category['children'], $search);
                $total = array_sum(array_column($children, 'productsCount'));

                if ($total > 0) {
                    $result[] = array_merge($category, [
                        'productsCount' => $total,
                        'children' => $children,
                    ]);
                }
            } else {
                [$titleQuery, $codeQuery] = $this->prepareSearchString($search);

                $productsCount = $this->createQueryBuilder('p')
                    ->select(['COUNT(p.id)'])
                    ->leftJoin('p.category', 'category')
                    ->orWhere($titleQuery)
                    ->orWhere($codeQuery)
                    ->andWhere('category.id = :id')
                    ->andWhere('p.status = :status')
                    ->setParameter('id', $category['id'])
                    ->setParameter('status', Product::STATUS_ACTIVE)
                    ->getQuery()
                    ->getSingleScalarResult();

                if ($productsCount > 0) {
                    $result[] = array_merge($category, ['productsCount' => $productsCount]);
                }
            }
        }

        return $result;
    }

    public function findBySearch(string $search, ?string $vendors, ?string $order, ?string $direction): QueryBuilder
    {
        [$titleQuery, $codeQuery] = $this->prepareSearchString($search);

        $query = $this->createQueryBuilder('p')
            ->orWhere($titleQuery)
            ->orWhere($codeQuery)
            ->andWhere('p.status = :status')
            ->setParameter('status', Product::STATUS_ACTIVE);

        if ($vendors) {
            $query = $query
                ->leftJoin('p.filterAttributes', 'filterAttributes')
                ->leftJoin('filterAttributes.filterAttribute', 'filterAttribute')
                ->andWhere('filterAttribute.value IN (:vendors)')
                ->setParameter('vendors', explode(',', $vendors))
            ;
        }

        if ($order && $direction) {
            $query->orderBy('p.' . $order, $direction);
        }

        return $query;
    }

    private function prepareSearchString(string $search): array
    {
        $words = explode(' ', trim($search));
        $titleConditions = [];
        $codeConditions = [];

        foreach ($words as $word) {
            $wordEscaped = str_replace("'", "''", trim($word));
            $titleConditions[] = "p.title LIKE '%" . $wordEscaped . "%'";
            $codeConditions[] = "p.productCode LIKE '%" . $wordEscaped . "%'";
        }

        $titleQuery = '(' . implode(' AND ', $titleConditions) . ')';
        $codeQuery = '(' . implode(' AND ', $codeConditions) . ')';

        return [$titleQuery, $codeQuery];
    }

    public function create(Product $product): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($product);
        $entityManager->flush();
    }
}
