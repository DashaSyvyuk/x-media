<?php

namespace App\Repository;

use App\Entity\Promotion;
use Carbon\Carbon;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Promotion|null find($id, $lockMode = null, $lockVersion = null)
 * @method Promotion|null findOneBy(array $criteria, array $orderBy = null)
 * @method Promotion[]    findAll()
 * @method Promotion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PromotionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Promotion::class);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function getActivePromotionBySlug(string $slug): ?Promotion
    {
        $now = Carbon::now();

        return $this->createQueryBuilder('p')
            ->andWhere('p.activeFrom <= :now')
            ->andWhere('p.activeTo >= :now')
            ->andWhere('p.status = :status')
            ->andWhere('p.slug = :slug')
            ->setParameter('now', $now)
            ->setParameter('status', Promotion::ACTIVE)
            ->setParameter('slug', $slug)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getActivePromotions()
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.activeFrom <= :now')
            ->andWhere('p.activeTo >= :now')
            ->andWhere('p.status = :status')
            ->setParameter('now', Carbon::now())
            ->setParameter('status', Promotion::ACTIVE)
            ->getQuery()
            ->getResult();
    }
}
