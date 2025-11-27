<?php

namespace App\Repository;

use App\Entity\Promotion;
use App\Entity\Slider;
use Carbon\Carbon;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Slider>
 */
class SliderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Slider::class);
    }

    public function getActiveItems(): mixed
    {
        $now = Carbon::now();

        return $this->createQueryBuilder('s')
            ->leftJoin('s.promotion', 'p')
            ->where('s.active = :active')
            ->andWhere('p.activeFrom <= :now OR p.activeFrom is NULL')
            ->andWhere('p.activeTo >= :now OR p.activeTo is NULL')
            ->andWhere('p.status = :status OR p.status is NULL')
            ->setParameter('active', true)
            ->setParameter('now', $now)
            ->setParameter('status', Promotion::ACTIVE)
            ->orderBy('s.priority', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
