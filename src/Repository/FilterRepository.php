<?php

namespace App\Repository;

use App\Entity\Filter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
            ->addOrderBy('fp.priority', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
