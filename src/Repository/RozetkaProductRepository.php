<?php

namespace App\Repository;

use App\Entity\RozetkaProduct;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RozetkaProduct>
 */
class RozetkaProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RozetkaProduct::class);
    }

    public function create(RozetkaProduct $rozetkaProduct): RozetkaProduct
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($rozetkaProduct);
        $entityManager->flush();

        return $rozetkaProduct;
    }

    public function update(RozetkaProduct $rozetkaProduct): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->refresh($rozetkaProduct);
        $entityManager->flush();
    }

    public function createAdminListQueryBuilder(
        string $search = '',
        ?string $ready = null,
        ?string $feed = null,
        string $sort = 'productId',
        string $direction = 'DESC',
    ): \Doctrine\ORM\QueryBuilder {
        $qb = $this->createQueryBuilder('rp')
            ->leftJoin('rp.product', 'product')
            ->addSelect('product')
            ->leftJoin('product.category', 'category')
            ->addSelect('category');

        $search = trim($search);
        if ($search !== '') {
            $orX = $qb->expr()->orX();

            if (ctype_digit($search)) {
                $orX->add($qb->expr()->eq('product.id', ':searchId'));
                $qb->setParameter('searchId', (int) $search);
            }

            $orX->add($qb->expr()->like('LOWER(rp.title)', ':searchText'));
            $orX->add($qb->expr()->like('LOWER(product.productCode)', ':searchText'));
            $orX->add($qb->expr()->like('LOWER(category.title)', ':searchText'));
            $qb->setParameter('searchText', '%' . mb_strtolower($search) . '%');

            $qb->andWhere($orX);
        }

        if ($ready === '1') {
            $qb->andWhere('rp.ready = :ready')->setParameter('ready', true);
        } elseif ($ready === '0') {
            $qb->andWhere('rp.ready = :ready')->setParameter('ready', false);
        }

        if ($feed === 'a') {
            $qb->andWhere('rp.activeForA = :activeForA')->setParameter('activeForA', true);
        } elseif ($feed === 'p') {
            $qb->andWhere('rp.activeForP = :activeForP')->setParameter('activeForP', true);
        }

        $allowedSorts = [
            'productId' => 'product.id',
            'title'     => 'rp.title',
            'price'     => 'rp.price',
            'updatedAt' => 'rp.updatedAt',
        ];
        $sortField = $allowedSorts[$sort] ?? 'product.id';
        $qb->orderBy($sortField, strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC');

        return $qb;
    }

    /**
     * @return array{total: int, ready: int, activeA: int, activeP: int}
     */
    public function getSummaryCounts(): array
    {
        return [
            'total'   => (int) $this->createQueryBuilder('rp')->select('COUNT(rp.id)')->getQuery()
                ->getSingleScalarResult(),
            'ready'   => (int) $this->createQueryBuilder('rp')->select('COUNT(rp.id)')->andWhere('rp.ready = 1')
                ->getQuery()->getSingleScalarResult(),
            'activeA' => (int) $this->createQueryBuilder('rp')->select('COUNT(rp.id)')->andWhere('rp.activeForA = 1')
                ->getQuery()->getSingleScalarResult(),
            'activeP' => (int) $this->createQueryBuilder('rp')->select('COUNT(rp.id)')->andWhere('rp.activeForP = 1')
                ->getQuery()->getSingleScalarResult(),
        ];
    }

    /**
     * Change Rozetka price for cards in the category by delta (can be negative).
     * Resulting price is never below 1.
     * Uses a single SQL UPDATE to avoid timeouts on large categories.
     */
    public function adjustPriceForCategory(int $categoryId, int $delta): int
    {
        if ($delta === 0) {
            return 0;
        }

        $connection = $this->getEntityManager()->getConnection();
        $updated = (int) $connection->executeStatement(
            'UPDATE rozetka_product rp
             INNER JOIN product p ON p.id = rp.product_id
             SET
                rp.crossed_out_price = CASE
                    WHEN rp.crossed_out_price IS NOT NULL
                         AND rp.crossed_out_price > 0
                         AND rp.crossed_out_price <= GREATEST(1, COALESCE(rp.price, 0) + :delta)
                    THEN NULL
                    ELSE rp.crossed_out_price
                END,
                rp.promo_price = CASE
                    WHEN rp.promo_price IS NOT NULL
                         AND rp.promo_price > 0
                         AND rp.promo_price >= GREATEST(1, COALESCE(rp.price, 0) + :delta)
                    THEN NULL
                    ELSE rp.promo_price
                END,
                rp.price = GREATEST(1, COALESCE(rp.price, 0) + :delta),
                rp.updated_at = :updatedAt
             WHERE p.category_id = :categoryId',
            [
                'delta'      => $delta,
                'categoryId' => $categoryId,
                'updatedAt'  => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ],
        );

        $this->getEntityManager()->clear();

        return $updated;
    }
}
