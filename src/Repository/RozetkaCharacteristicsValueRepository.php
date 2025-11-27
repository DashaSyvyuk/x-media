<?php

namespace App\Repository;

use App\Entity\RozetkaCharacteristicsValue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RozetkaCharacteristicsValue>
 */
class RozetkaCharacteristicsValueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RozetkaCharacteristicsValue::class);
    }

    public function create(RozetkaCharacteristicsValue $rozetkaCharacteristics): RozetkaCharacteristicsValue
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($rozetkaCharacteristics);
        $entityManager->flush();

        return $rozetkaCharacteristics;
    }

    public function update(RozetkaCharacteristicsValue $rozetkaCharacteristics): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->refresh($rozetkaCharacteristics);
        $entityManager->flush();
    }
}
