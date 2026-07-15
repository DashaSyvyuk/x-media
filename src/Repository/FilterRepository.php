<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Filter;
use App\Entity\Promotion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Filter>
 */
class FilterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Filter::class);
    }

    public function findByCategory(string $slug): mixed
    {
        return $this->createQueryBuilder('fp')
            ->leftJoin('fp.category', 'category')
            ->andWhere('category.slug = :slug')
            ->setParameter('slug', $slug)
            ->addOrderBy('fp.priority', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @throws NonUniqueResultException
     *
     * @param array<int, string> $attributes
     *
     * @return array<int, array<mixed>>
     */
    public function findByFilterAttributes(?array $attributes): array
    {
        $result = [];

        if (!empty($attributes)) {
            foreach ($attributes as $attribute) {
                $filter = $this->createQueryBuilder('fp')
                    ->leftJoin('fp.attributes', 'attribute')
                    ->andWhere('attribute.id = :id')
                    ->setParameter('id', $attribute)
                    ->getQuery()
                    ->getOneOrNullResult();

                $result[$filter->getId()][] = $attribute;
            }
        }

        return $result;
    }

    public function createAdminListQueryBuilder(
        string $search = '',
        ?int $categoryId = null,
        string $sort = 'title',
        string $direction = 'ASC',
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('f')
            ->leftJoin('f.category', 'category')
            ->addSelect('category');

        $search = trim($search);
        if ($search !== '') {
            $qb->andWhere('LOWER(f.title) LIKE :search OR LOWER(category.title) LIKE :search')
                ->setParameter('search', '%' . mb_strtolower($search) . '%');
        }

        if ($categoryId !== null && $categoryId > 0) {
            $qb->andWhere('category.id = :categoryId')
                ->setParameter('categoryId', $categoryId);
        }

        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        if ($sort === 'category') {
            $qb->orderBy('category.title', $direction);
        } else {
            $allowedSorts = ['id', 'title', 'priority', 'openedCount'];
            if (! in_array($sort, $allowedSorts, true)) {
                $sort = 'title';
            }
            $qb->orderBy('f.' . $sort, $direction);
        }

        return $qb;
    }
}
