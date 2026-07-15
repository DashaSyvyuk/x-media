<?php

namespace App\Repository;

use App\Entity\Promotion;
use App\Entity\Slider;
use Carbon\Carbon;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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

    public function createAdminListQueryBuilder(
        string $search = '',
        string $sort = 'id',
        string $direction = 'DESC',
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.promotion', 'promotion')
            ->addSelect('promotion');

        $search = trim($search);
        if ($search !== '') {
            $qb->andWhere('LOWER(s.title) LIKE :search')
                ->setParameter('search', '%' . mb_strtolower($search) . '%');
        }

        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
        $allowedSorts = ['id', 'title', 'priority', 'active'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'id';
        }

        $qb->orderBy('s.' . $sort, $direction);

        return $qb;
    }
}
