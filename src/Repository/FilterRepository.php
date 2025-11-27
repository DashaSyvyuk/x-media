<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Filter;
use App\Entity\Promotion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
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
}
