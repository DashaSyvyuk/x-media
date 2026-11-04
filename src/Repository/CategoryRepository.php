<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\Promotion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * Build the category tree with active-product counts.
     *
     * Loads all active categories + one GROUP BY count query instead of the old
     * recursive approach that ran N+1 queries and hydrated every Product just to
     * call ->count() (multi-second / 100MB+ on every storefront page).
     *
     * @return list<array<string, mixed>>
     */
    public function getCategoriesTree(?int $parent = null): array
    {
        /** @var list<Category> $categories */
        $categories = $this->createQueryBuilder('c')
            ->leftJoin('c.parent', 'parent')->addSelect('parent')
            ->andWhere('c.status = :status')
            ->setParameter('status', Category::ACTIVE)
            ->orderBy('c.position', 'ASC')
            ->addOrderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult();

        $countRows = $this->getEntityManager()->createQueryBuilder()
            ->select('IDENTITY(p.category) AS categoryId', 'COUNT(p.id) AS productsCount')
            ->from(Product::class, 'p')
            ->andWhere('p.status = :productStatus')
            ->andWhere('p.category IS NOT NULL')
            ->setParameter('productStatus', Product::STATUS_ACTIVE)
            ->groupBy('p.category')
            ->getQuery()
            ->getScalarResult();

        /** @var array<int, int> $productCounts */
        $productCounts = [];
        foreach ($countRows as $row) {
            $productCounts[(int) $row['categoryId']] = (int) $row['productsCount'];
        }

        /** @var array<string, list<Category>> $byParent */
        $byParent = [];
        foreach ($categories as $category) {
            $parentId = $category->getParent()?->getId();
            $key = $parentId === null ? 'root' : (string) $parentId;
            $byParent[$key][] = $category;
        }

        $build = function (?int $parentId) use (&$build, $byParent, $productCounts): array {
            $key = $parentId === null ? 'root' : (string) $parentId;
            $result = [];

            foreach ($byParent[$key] ?? [] as $category) {
                $id = $category->getId();
                $result[] = [
                    'id'            => $id,
                    'title'         => $category->getTitle(),
                    'image'         => $category->getImage(),
                    'imageUri'      => $category->getImageUri(),
                    'slug'          => $category->getSlug(),
                    'showInHeader'  => $category->getShowInHeader(),
                    'children'      => $build($id),
                    'productsCount' => $productCounts[$id] ?? 0,
                ];
            }

            return $result;
        };

        return $build($parent);
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function getCategoriesForProm(): array
    {
        $result = [];
        $categories = $this->createQueryBuilder('c')
            ->leftJoin('c.products', 'p')
            ->andWhere('c.status = :status')
            ->andWhere('c.promCategoryLink IS NOT NULL')
            ->setParameter('status', 'ACTIVE')
            ->andWhere('p.status = :product_status')
            ->setParameter('product_status', Product::STATUS_ACTIVE)
            ->andWhere('c.showInPromFeed = :showInPromFeed')
            ->setParameter('showInPromFeed', true)
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

    public function getCategoriesForHotline(): mixed
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.products', 'p')
            ->andWhere('c.status = :status')
            ->andWhere('c.hotlineCategory IS NOT NULL')
            ->setParameter('status', 'ACTIVE')
            ->andWhere('p.status = :product_status')
            ->setParameter('product_status', Product::STATUS_ACTIVE)
            ->andWhere('c.showInHotlineFeed = :showInHotlineFeed')
            ->setParameter('showInHotlineFeed', true)
            ->orderBy('c.title', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function getCategoriesForRozetka(string $activeFor): mixed
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.products', 'p')
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
            ->orderBy('c.title', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }

    public function getCategoriesForEkatalog(): mixed
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.products', 'p')
            ->andWhere('c.status = :status')
            ->setParameter('status', 'ACTIVE')
            ->andWhere('p.status = :product_status')
            ->setParameter('product_status', Product::STATUS_ACTIVE)
            ->andWhere('c.showInEkatalogFeed = :showInEkatalogFeed')
            ->setParameter('showInEkatalogFeed', true)
            ->orderBy('c.title', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }

    /**
     * @return array<int, array{id: int, title: string}>
     */
    public function getUploadCharacteristicCategories(): array
    {
        /** @var array<int, array{id: int, title: string}> $result */
        $result = $this->createQueryBuilder('c')
            ->select('c.id', 'c.title')
            ->getQuery()
            ->getArrayResult();

        return $result;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCategoriesIdsWithoutChildren(): array
    {
        $categories = $this->getCategoriesTree();

        return $this->getCategoriesId($categories);
    }

    /**
     * @param array<int, array<string, mixed>> $categories
     *
     * @return array<int, array<string, mixed>>
     */
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

    public function createAdminListQueryBuilder(
        string $search = '',
        ?string $status = null,
        string $sort = 'title',
        string $direction = 'ASC',
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.parent', 'parent')
            ->addSelect('parent');

        $search = trim($search);
        if ($search !== '') {
            $qb->andWhere('LOWER(c.title) LIKE :search OR LOWER(c.slug) LIKE :search')
                ->setParameter('search', '%' . mb_strtolower($search) . '%');
        }

        if ($status !== null && $status !== '') {
            $qb->andWhere('c.status = :status')
                ->setParameter('status', $status);
        }

        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        if ($sort === 'parent') {
            $qb->orderBy('parent.title', $direction);
        } else {
            $allowedSorts = ['id', 'title', 'slug', 'position', 'status'];
            if (! in_array($sort, $allowedSorts, true)) {
                $sort = 'title';
            }
            $qb->orderBy('c.' . $sort, $direction);
        }

        return $qb;
    }
}
