<?php

namespace App\Repository;

use App\Entity\AdminUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<AdminUser>
 */
class AdminUserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdminUser::class);
    }

    /**
     */
    public function add(AdminUser $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     */
    public function remove(AdminUser $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return list<AdminUser>
     */
    public function findActiveOrderedByName(): array
    {
        /** @var list<AdminUser> $users */
        $users = $this->createQueryBuilder('u')
            ->andWhere('u.active = :active')
            ->setParameter('active', true)
            ->orderBy('u.name', 'ASC')
            ->addOrderBy('u.surname', 'ASC')
            ->getQuery()
            ->getResult();

        return $users;
    }

    /**
     * Active admins who opted in to local new-order notifications (email + push).
     *
     * @return list<string>
     */
    public function findEmailsForLocalOrderNotifications(): array
    {
        /** @var list<string> $emails */
        $emails = $this->createQueryBuilder('u')
            ->select('u.email')
            ->andWhere('u.active = true')
            ->andWhere('u.notifyLocalOrders = true')
            ->getQuery()
            ->getSingleColumnResult();

        $unique = [];
        foreach ($emails as $email) {
            $email = trim($email);
            if ($email === '') {
                continue;
            }
            $unique[strtolower($email)] = $email;
        }

        return array_values($unique);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof AdminUser) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }
}
