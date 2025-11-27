<?php

namespace App\Repository;

use App\Entity\User;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
}
