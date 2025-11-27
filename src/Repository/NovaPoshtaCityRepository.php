<?php

namespace App\Repository;

use App\Entity\NovaPoshtaCity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NovaPoshtaCity>
 */
class NovaPoshtaCityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NovaPoshtaCity::class);
    }

    public function create(NovaPoshtaCity $city): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($city);
        $entityManager->flush();
    }

    public function update(NovaPoshtaCity $city): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->refresh($city);
        $entityManager->flush();
    }

    /**
     * @return array<mixed>
     */
    public function getCitiesWithOffices(): array
    {
        return $this->createQueryBuilder('city')
            ->innerJoin('city.offices', 'offices')
            ->groupBy('city.id')
            ->getQuery()
            ->getArrayResult();
    }
}
