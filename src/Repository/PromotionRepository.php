<?php

namespace App\Repository;

use App\Entity\Promotion;
use App\Entity\PromotionProduct;
use Carbon\Carbon;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Promotion>
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

    public function getActivePromotions(): mixed
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

    public function createAdminListQueryBuilder(
        string $search = '',
        string $sort = 'id',
        string $direction = 'DESC',
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('p');

        $search = trim($search);
        if ($search !== '') {
            $qb->andWhere('LOWER(p.title) LIKE :search OR LOWER(p.slug) LIKE :search')
                ->setParameter('search', '%' . mb_strtolower($search) . '%');
        }

        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
        $allowedSorts = ['id', 'title', 'slug', 'status', 'activeFrom', 'activeTo'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'id';
        }

        $qb->orderBy('p.' . $sort, $direction);

        return $qb;
    }

    /**
     * @param array<int, int> $promotionIds
     *
     * @return array<int, int>
     */
    public function countProductsByPromotionIds(array $promotionIds): array
    {
        if ($promotionIds === []) {
            return [];
        }

        $rows = $this->getEntityManager()->createQueryBuilder()
            ->select('IDENTITY(pp.promotion) AS promotionId', 'COUNT(pp.id) AS productsCount')
            ->from(PromotionProduct::class, 'pp')
            ->where('pp.promotion IN (:ids)')
            ->groupBy('pp.promotion')
            ->setParameter('ids', $promotionIds)
            ->getQuery()
            ->getScalarResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row['promotionId']] = (int) $row['productsCount'];
        }

        return $counts;
    }
}
