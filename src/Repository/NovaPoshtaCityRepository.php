<?php

namespace App\Repository;

use App\Entity\NovaPoshtaCity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method NovaPoshtaCity|null find($id, $lockMode = null, $lockVersion = null)
 * @method NovaPoshtaCity|null findOneBy(array $criteria, array $orderBy = null)
 * @method NovaPoshtaCity[]    findAll()
 * @method NovaPoshtaCity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NovaPoshtaCityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NovaPoshtaCity::class);
    }

    public function create(NovaPoshtaCity $city)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($city);
        $entityManager->flush();
    }

    public function update(NovaPoshtaCity $city)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->merge($city);
        $entityManager->flush();
    }

    public function getCitiesWithOffices()
    {
        return $this->createQueryBuilder('city')
            ->innerJoin('city.offices', 'offices')
            ->groupBy('city.id')
            ->getQuery()
            ->getArrayResult();
    }
}
