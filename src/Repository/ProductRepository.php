<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\Promotion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * @param array<int, array<mixed>> $attributes
     */
    public function findByCategoryAndAttributes(
        ?Category $category,
        array $attributes,
        ?string $search,
        ?string $order,
        ?string $direction,
        ?int $priceFrom,
        ?int $priceTo
    ): QueryBuilder {
        $query = $this->prepareQuery($category, $attributes, $search, $priceFrom, $priceTo);

        if ($order && $direction) {
            $query->orderBy('p.' . $order, $direction);
        }

        return $query;
    }

    /**
     * @param array<int, array<mixed>> $attributes
     */
    private function prepareQuery(
        ?Category $category,
        array $attributes,
        ?string $search,
        ?int $priceFrom,
        ?int $priceTo
    ): QueryBuilder {
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
                    ->leftJoin(
                        sprintf('productFilterAttribute%d.filterAttribute', $filter),
                        sprintf('filterAttribute%d', $filter)
                    )
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

    /**
     * @param array<int, array<mixed>> $attributes
     *
     * @return array<string, string>
     */
    public function getMinAndMaxPriceInCategory(
        ?Category $category,
        array $attributes,
        ?string $search,
        ?int $priceFrom,
        ?int $priceTo
    ): array {
        $query = $this->prepareQuery($category, $attributes, $search, $priceFrom, $priceTo);

        $query->select('MIN(p.price) AS min_price, MAX(p.price) AS max_price');

        return $query->getQuery()->getArrayResult()[0];
    }

    /**
     * @return iterable<Product>
     */
    public function getProductsForProm(): iterable
    {
        return $this->createQueryBuilder('p')
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
            ->toIterable()
        ;
    }

    /**
     * @return iterable<Product>
     */
    public function getProductsForHotline(): iterable
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
            ->toIterable()
        ;
    }

    /**
     * @return iterable<Product>
     */
    public function getProductsForEkatalog(): iterable
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
            ->toIterable()
            ;
    }

    /**
     * @return iterable<Product>
     */
    public function getProductsForRozetka(string $activeFor): iterable
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->leftJoin('p.rozetka', 'rozetka')
            ->andWhere('c.status = :status')
            ->andWhere('c.rozetkaCategory IS NOT NULL')
            ->setParameter('status', 'ACTIVE')
            ->andWhere('p.status = :product_status')
            ->setParameter('product_status', Product::STATUS_ACTIVE)
            ->andWhere('c.showInRozetkaFeed = :showInRozetkaFeed')
            ->setParameter('showInRozetkaFeed', true)
            ->andWhere(sprintf('rozetka.%s = :active', $activeFor))
            ->setParameter('active', true)
            ->orderBy('p.title', 'ASC')
            ->getQuery()
            ->toIterable()
            ;
    }

    /**
     * Lightweight ID list for chunked Rozetka XML generation.
     *
     * @return list<int>
     */
    public function getProductIdsForRozetka(string $activeFor): array
    {
        /** @var list<int|string> $ids */
        $ids = $this->createQueryBuilder('p')
            ->select('p.id')
            ->innerJoin('p.category', 'c')
            ->innerJoin('p.rozetka', 'rozetka')
            ->andWhere('c.status = :status')
            ->andWhere('c.rozetkaCategory IS NOT NULL')
            ->setParameter('status', 'ACTIVE')
            ->andWhere('p.status = :product_status')
            ->setParameter('product_status', Product::STATUS_ACTIVE)
            ->andWhere('c.showInRozetkaFeed = :showInRozetkaFeed')
            ->setParameter('showInRozetkaFeed', true)
            ->andWhere(sprintf('rozetka.%s = :active', $activeFor))
            ->setParameter('active', true)
            ->orderBy('p.title', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();

        return array_map('intval', $ids);
    }

    /**
     * Hydrate a chunk of products with associations needed for Rozetka XML.
     * Collections are warmed in separate queries to avoid cartesian explosion.
     *
     * @param list<int> $ids
     *
     * @return list<Product>
     */
    public function getProductsForRozetkaByIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($ids === []) {
            return [];
        }

        /** @var list<Product> $products */
        $products = $this->createQueryBuilder('p')
            ->addSelect('c', 'rozetka')
            ->innerJoin('p.category', 'c')
            ->innerJoin('p.rozetka', 'rozetka')
            ->andWhere('p.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        // Warm images into the identity map.
        $this->createQueryBuilder('p')
            ->addSelect('images')
            ->leftJoin('p.images', 'images')
            ->andWhere('p.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        // Warm Rozetka characteristic values (+ related titles).
        $this->createQueryBuilder('p')
            ->addSelect('rozetka', 'vals', 'characteristic', 'singleValue', 'multiValues')
            ->innerJoin('p.rozetka', 'rozetka')
            ->leftJoin('rozetka.values', 'vals')
            ->leftJoin('vals.characteristic', 'characteristic')
            ->leftJoin('vals.value', 'singleValue')
            ->leftJoin('vals.values', 'multiValues')
            ->andWhere('p.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        $byId = [];
        foreach ($products as $product) {
            $byId[$product->getId()] = $product;
        }

        $ordered = [];
        foreach ($ids as $id) {
            if (isset($byId[$id])) {
                $ordered[] = $byId[$id];
            }
        }

        return $ordered;
    }

    /**
     * Vendor titles keyed by product id (Марка / Виробник).
     *
     * @param list<int> $ids
     *
     * @return array<int, string>
     */
    public function getRozetkaVendorsByProductIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($ids === []) {
            return [];
        }

        /** @var list<array{productId: int|string, vendor: string}> $rows */
        $rows = $this->getEntityManager()->createQueryBuilder()
            ->select('IDENTITY(pfa.product) AS productId', 'attr.value AS vendor')
            ->from(\App\Entity\ProductFilterAttribute::class, 'pfa')
            ->innerJoin('pfa.filter', 'filter')
            ->innerJoin('pfa.filterAttribute', 'attr')
            ->andWhere('IDENTITY(pfa.product) IN (:ids)')
            ->andWhere('filter.title IN (:titles)')
            ->setParameter('ids', $ids)
            ->setParameter('titles', ['Марка', 'Виробник'])
            ->getQuery()
            ->getArrayResult();

        $vendors = [];
        foreach ($rows as $row) {
            $productId = (int) $row['productId'];
            if (! isset($vendors[$productId]) && $row['vendor'] !== '') {
                $vendors[$productId] = (string) $row['vendor'];
            }
        }

        return $vendors;
    }

    public function findByPromotionAndVendor(
        Promotion $promotion,
        ?Category $category,
        ?string $vendors,
        ?string $order,
        ?string $direction,
    ): QueryBuilder {
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
     *
     * @param array<int, array<string, mixed>> $categories
     *
     * @return array<int, array<string, mixed>>
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
     *
     * @param array<int, array<string, mixed>> $categories
     *
     * @return array<int, array<string, mixed>>
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

    public function createAdminListQueryBuilder(
        string $search = '',
        ?string $status = null,
        string $sort = 'id',
        string $direction = 'DESC',
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'category')
            ->addSelect('category');

        $search = trim($search);
        if ($search !== '') {
            $words = preg_split('/\s+/', $search) ?: [];
            $orX = $qb->expr()->orX();

            foreach (['title', 'productCode'] as $field) {
                $andX = $qb->expr()->andX();
                foreach ($words as $k => $word) {
                    if ($word === '') {
                        continue;
                    }
                    $paramName = sprintf('%s_word%d', $field, $k);
                    $andX->add($qb->expr()->like("LOWER(p.$field)", ":$paramName"));
                    $qb->setParameter($paramName, '%' . mb_strtolower($word) . '%');
                }
                if ($andX->count() > 0) {
                    $orX->add($andX);
                }
            }

            if (ctype_digit($search)) {
                $orX->add($qb->expr()->eq('p.id', ':searchId'));
                $qb->setParameter('searchId', (int) $search);
            }

            $qb->andWhere($orX);
        }

        if ($status !== null && $status !== '') {
            $qb->andWhere('p.status = :status')
                ->setParameter('status', $status);
        }

        $allowedSorts = ['id', 'title', 'price', 'updatedAt', 'createdAt'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'id';
        }
        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';

        return $qb->orderBy('p.' . $sort, $direction);
    }

    public function createPriceControlQueryBuilder(
        string $search = '',
        ?string $status = null,
        ?int $categoryId = null,
        string $sort = 'id',
        string $direction = 'DESC',
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'category')
            ->addSelect('category')
            ->leftJoin('p.rozetka', 'rozetka')
            ->addSelect('rozetka');

        $search = trim($search);
        if ($search !== '') {
            $orX = $qb->expr()->orX();

            if (ctype_digit($search)) {
                $orX->add($qb->expr()->eq('p.id', ':searchId'));
                $qb->setParameter('searchId', (int) $search);
            }

            $orX->add($qb->expr()->like('LOWER(p.productCode)', ':searchText'));
            $orX->add($qb->expr()->like('LOWER(p.productCode2)', ':searchText'));
            $orX->add($qb->expr()->like('LOWER(p.title)', ':searchText'));
            $qb->setParameter('searchText', '%' . mb_strtolower($search) . '%');
            $qb->andWhere($orX);
        }

        if ($status !== null && $status !== '') {
            $qb->andWhere('p.status = :status')
                ->setParameter('status', $status);
        }

        if ($categoryId !== null && $categoryId > 0) {
            $qb->andWhere('category.id = :categoryId')
                ->setParameter('categoryId', $categoryId);
        }

        $allowedSorts = [
            'id'    => 'p.id',
            'title' => 'p.title',
            'price' => 'p.price',
        ];
        if (! isset($allowedSorts[$sort])) {
            $sort = 'id';
        }
        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';

        return $qb->orderBy($allowedSorts[$sort], $direction);
    }

    /**
     * @return list<array{id: int, title: string, price: int, productCode: string}>
     */
    public function searchForAdminPicker(string $search, int $limit = 15): array
    {
        $search = ltrim(trim($search), '#');
        if ($search === '') {
            return [];
        }

        $qb = $this->createQueryBuilder('p')
            ->select('p.id', 'p.title', 'p.price', 'p.productCode');

        if (ctype_digit($search)) {
            $exact = $this->findAdminPickerItem((int) $search);
            if ($exact !== null) {
                return [$exact];
            }

            $qb->where($qb->expr()->like('CONCAT(\'\', p.id)', ':idPrefix'))
                ->setParameter('idPrefix', $search . '%')
                ->orderBy('p.id', 'ASC');
        } else {
            $words = preg_split('/\s+/', mb_strtolower($search)) ?: [];
            $orX   = $qb->expr()->orX();

            foreach (['title', 'productCode'] as $field) {
                $andX = $qb->expr()->andX();
                foreach ($words as $k => $word) {
                    if ($word === '') {
                        continue;
                    }
                    $paramName = sprintf('%s_word%d', $field, $k);
                    $andX->add($qb->expr()->like("LOWER(p.$field)", ":$paramName"));
                    $qb->setParameter($paramName, '%' . $word . '%');
                }
                if ($andX->count() > 0) {
                    $orX->add($andX);
                }
            }

            $qb->where($orX)->orderBy('p.id', 'DESC');
        }

        $rows = $qb->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn(array $row): array => [
            'id'          => (int) $row['id'],
            'title'       => (string) $row['title'],
            'price'       => (int) $row['price'],
            'productCode' => (string) $row['productCode'],
        ], $rows);
    }

    /**
     * @return array{id: int, title: string, price: int, productCode: string}|null
     */
    public function findAdminPickerItem(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $product = $this->find($id);
        if ($product === null) {
            return null;
        }

        return [
            'id'          => $product->getId(),
            'title'       => (string) $product->getTitle(),
            'price'       => $product->getPrice(),
            'productCode' => (string) $product->getProductCode(),
        ];
    }

    /**
     * Change site price for every product in the category by delta (can be negative).
     * Resulting price is never below 1. Clears crossed-out when invalid.
     * Uses a single SQL UPDATE to avoid timeouts on large categories.
     */
    public function adjustPriceForCategory(int $categoryId, int $delta): int
    {
        if ($delta === 0) {
            return 0;
        }

        $connection = $this->getEntityManager()->getConnection();
        $updated = (int) $connection->executeStatement(
            'UPDATE product
             SET
                crossed_out_price = CASE
                    WHEN crossed_out_price IS NOT NULL
                         AND crossed_out_price > 0
                         AND crossed_out_price <= GREATEST(1, COALESCE(price, 0) + :delta)
                    THEN NULL
                    ELSE crossed_out_price
                END,
                price = GREATEST(1, COALESCE(price, 0) + :delta),
                updated_at = :updatedAt
             WHERE category_id = :categoryId',
            [
                'delta'      => $delta,
                'categoryId' => $categoryId,
                'updatedAt'  => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ],
        );

        $this->getEntityManager()->clear();

        return $updated;
    }

    /**
     * @param list<int> $ids
     */
    public function adjustPriceForIds(array $ids, int $delta): int
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($ids === [] || $delta === 0) {
            return 0;
        }

        $connection = $this->getEntityManager()->getConnection();
        $updated = (int) $connection->executeStatement(
            'UPDATE product
             SET
                crossed_out_price = CASE
                    WHEN crossed_out_price IS NOT NULL
                         AND crossed_out_price > 0
                         AND crossed_out_price <= GREATEST(1, COALESCE(price, 0) + :delta)
                    THEN NULL
                    ELSE crossed_out_price
                END,
                price = GREATEST(1, COALESCE(price, 0) + :delta),
                updated_at = :updatedAt
             WHERE id IN (:ids)',
            [
                'delta'     => $delta,
                'ids'       => $ids,
                'updatedAt' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ],
            [
                'ids' => ArrayParameterType::INTEGER,
            ],
        );

        $this->getEntityManager()->clear();

        return $updated;
    }

    /**
     * @param list<int> $ids
     */
    public function setStatusForIds(array $ids, string $status): int
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($ids === [] || ! in_array($status, [Product::STATUS_ACTIVE, Product::STATUS_BLOCKED], true)) {
            return 0;
        }

        $connection = $this->getEntityManager()->getConnection();
        $updated = (int) $connection->executeStatement(
            'UPDATE product
             SET status = :status, updated_at = :updatedAt
             WHERE id IN (:ids)',
            [
                'status'    => $status,
                'ids'       => $ids,
                'updatedAt' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ],
            [
                'ids' => ArrayParameterType::INTEGER,
            ],
        );

        $this->getEntityManager()->clear();

        return $updated;
    }
}
