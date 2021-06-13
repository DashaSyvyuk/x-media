<?php

namespace App\Repository;

use App\Entity\FilterParameter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method FilterParameter|null find($id, $lockMode = null, $lockVersion = null)
 * @method FilterParameter|null findOneBy(array $criteria, array $orderBy = null)
 * @method FilterParameter[]    findAll()
 * @method FilterParameter[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FilterParameterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FilterParameter::class);
    }

    public function findByCategory(string $slug)
    {
        return $this->createQueryBuilder('fp')
            ->leftJoin('fp.values', 'values')
            ->leftJoin('values.product', 'product')
            ->leftJoin('product.category', 'category')
            ->andWhere('category.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getResult();
    }
}
