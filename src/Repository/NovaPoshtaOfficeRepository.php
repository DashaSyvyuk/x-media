<?php

namespace App\Repository;

use App\Entity\NovaPoshtaOffice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method NovaPoshtaOffice|null find($id, $lockMode = null, $lockVersion = null)
 * @method NovaPoshtaOffice|null findOneBy(array $criteria, array $orderBy = null)
 * @method NovaPoshtaOffice[]    findAll()
 * @method NovaPoshtaOffice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NovaPoshtaOfficeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NovaPoshtaOffice::class);
    }

    public function create(NovaPoshtaOffice $office)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($office);
        $entityManager->flush();
    }

    public function update(NovaPoshtaOffice $office)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->merge($office);
        $entityManager->flush();
    }

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
