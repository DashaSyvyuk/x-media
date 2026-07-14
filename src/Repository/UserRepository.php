<?php

namespace App\Repository;

use App\Entity\User;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @param array<string, string|bool|DateTime> $data
     */
    public function create(array $data): User
    {
        $user = new User();
        $user->setEmail($data['email']);
        $user->setName($data['name']);
        $user->setSurname($data['surname']);
        $user->setPhone($data['phone']);
        $user->setPassword($data['password']);
        $user->setConfirmed($data['confirmed']);
        $user->setHash($data['hash']);
        $user->setExpiredAt($data['expiredAt']);

        $entityManager = $this->getEntityManager();
        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }

    public function update(User $user): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->refresh($user);
        $entityManager->flush();
    }

    public function createAdminListQueryBuilder(
        string $search = '',
        string $sort = 'id',
        string $direction = 'DESC',
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('u');

        $search = trim($search);
        if ($search !== '') {
            $qb->andWhere(
                'LOWER(u.phone) LIKE :search
                OR LOWER(u.email) LIKE :search
                OR LOWER(u.name) LIKE :search
                OR LOWER(u.surname) LIKE :search',
            )->setParameter('search', '%' . mb_strtolower($search) . '%');
        }

        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
        $allowedSorts = ['id', 'name', 'surname', 'phone', 'email', 'createdAt'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'id';
        }

        $qb->orderBy('u.' . $sort, $direction);

        return $qb;
    }
}
