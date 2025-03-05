<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\RozetkaCharacteristics;
use App\Entity\RozetkaCharacteristicsValue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RozetkaCharacteristics|null find($id, $lockMode = null, $lockVersion = null)
 * @method RozetkaCharacteristics|null findOneBy(array $criteria, array $orderBy = null)
 * @method RozetkaCharacteristics[]    findAll()
 * @method RozetkaCharacteristics[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RozetkaCharacteristicsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private readonly RozetkaCharacteristicsValueRepository $valueRepository)
    {
        parent::__construct($registry, RozetkaCharacteristics::class);
    }

    public function fill(array $data): RozetkaCharacteristics
    {
        $characteristics = new RozetkaCharacteristics();
        $characteristics->setRozetkaId($data['rozetkaId']);
        $characteristics->setTitle($data['title']);
        $characteristics->setType($data['type']);
        $characteristics->setFilterType($data['filterType']);
        $characteristics->setUnit($data['unit']);
        $characteristics->setEndToEndParameter($data['endToEndParameter']);
        $characteristics->setActive($data['active']);
        $characteristics->addCategory($data['category']);

        if (!in_array($data['type'], ['TextArea', 'TextInput'])) {
            foreach ($data['values'] as $value) {
                $characteristicsValue = $this->valueRepository->findOneBy(['rozetkaId' => $value['rozetkaId']]);
                if ($characteristicsValue || !$value['title']) {
                    continue;
                }
                $characteristicsValue = new RozetkaCharacteristicsValue();
                $characteristicsValue->setRozetkaId($value['rozetkaId']);
                $characteristicsValue->setTitle($value['title']);
                $characteristicsValue->setActive($value['active']);

                $characteristics->addValue($characteristicsValue);
            }
        }

        return $characteristics;
    }

    public function create(RozetkaCharacteristics $characteristics): RozetkaCharacteristics
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($characteristics);
        $entityManager->flush();

        return $characteristics;
    }

    public function update(RozetkaCharacteristics $characteristics)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->merge($characteristics);
        $entityManager->flush();
    }

    public function getCharacteristicsForCategory(Category $category)
    {
        return $this->createQueryBuilder('char')
            ->where(':category MEMBER OF char.categories')
            ->setParameters(['category' => $category])
            ->andWhere('char.active = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();
    }
}
