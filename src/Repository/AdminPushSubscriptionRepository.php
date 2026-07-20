<?php

namespace App\Repository;

use App\Entity\AdminPushSubscription;
use App\Entity\AdminUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdminPushSubscription>
 */
class AdminPushSubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdminPushSubscription::class);
    }

    public function findOneByEndpoint(string $endpoint): ?AdminPushSubscription
    {
        return $this->findOneBy(['endpointHash' => hash('sha256', $endpoint)]);
    }

    /**
     * @return list<AdminPushSubscription>
     */
    public function findForLocalOrderNotifications(): array
    {
        /** @var list<AdminPushSubscription> $rows */
        $rows = $this->createQueryBuilder('s')
            ->innerJoin('s.user', 'u')
            ->andWhere('u.active = true')
            ->andWhere('u.notifyLocalOrders = true')
            ->getQuery()
            ->getResult();

        return $rows;
    }

    /**
     * @return list<AdminPushSubscription>
     */
    public function findForRozetkaOrderNotifications(): array
    {
        /** @var list<AdminPushSubscription> $rows */
        $rows = $this->createQueryBuilder('s')
            ->innerJoin('s.user', 'u')
            ->andWhere('u.active = true')
            ->andWhere('u.notifyRozetkaOrders = true')
            ->getQuery()
            ->getResult();

        return $rows;
    }

    /**
     * @return list<AdminPushSubscription>
     */
    public function findByUser(AdminUser $user): array
    {
        /** @var list<AdminPushSubscription> $rows */
        $rows = $this->findBy(['user' => $user], ['updatedAt' => 'DESC']);

        return $rows;
    }
}
