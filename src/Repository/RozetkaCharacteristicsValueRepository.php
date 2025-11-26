<?php

namespace App\Repository;

use App\Entity\RozetkaCharacteristicsValue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RozetkaCharacteristicsValue|null find($id, $lockMode = null, $lockVersion = null)
 * @method RozetkaCharacteristicsValue|null findOneBy(array $criteria, array $orderBy = null)
 * @method RozetkaCharacteristicsValue[] findAll()
 * @method RozetkaCharacteristicsValue[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
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

    public function update(RozetkaCharacteristicsValue $rozetkaCharacteristics)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->merge($rozetkaCharacteristics);
        $entityManager->flush();
    }
}
