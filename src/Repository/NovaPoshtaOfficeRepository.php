<?php

namespace App\Repository;

use App\Entity\NovaPoshtaOffice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NovaPoshtaOffice>
 */
class NovaPoshtaOfficeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NovaPoshtaOffice::class);
    }

    public function create(NovaPoshtaOffice $office): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($office);
        $entityManager->flush();
    }

    public function update(NovaPoshtaOffice $office): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->refresh($office);
        $entityManager->flush();
    }

    /**
     * @return array<mixed>
     */
    public function getOfficesByCityRef(string $cityRef): array
    {
        return $this->createQueryBuilder('office')
            ->leftJoin('office.city', 'city')
            ->andWhere('city.ref=:cityRef')
            ->setParameter('cityRef', $cityRef)
            ->getQuery()
            ->getArrayResult();
    }
}
