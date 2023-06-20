<?php

namespace App\Repository;

use App\Entity\Filter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Filter|null find($id, $lockMode = null, $lockVersion = null)
 * @method Filter|null findOneBy(array $criteria, array $orderBy = null)
 * @method Filter[]    findAll()
 * @method Filter[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FilterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Filter::class);
    }

    public function findByCategory(string $slug)
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
