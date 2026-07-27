<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\RozetkaCharacteristics;
use App\Entity\RozetkaCharacteristicsValue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RozetkaCharacteristics>
 */
class RozetkaCharacteristicsRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly RozetkaCharacteristicsValueRepository $valueRepository
    ) {
        parent::__construct($registry, RozetkaCharacteristics::class);
    }

    /**
     * @param array<string, Category|bool|list<array<string, bool|string>>|string> $data
     */
    public function fill(array $data): RozetkaCharacteristics
    {
        $characteristics = new RozetkaCharacteristics();
        $characteristics->setRozetkaId(intval($data['rozetkaId']));
        $characteristics->setTitle($data['title']);
        $characteristics->setType($data['type']);
        $characteristics->setFilterType($data['filterType']);
        $characteristics->setUnit($data['unit']);
        $characteristics->setEndToEndParameter($data['endToEndParameter']);
        $characteristics->setActive($data['active']);
        $characteristics->addCategory($data['category']);

        if (!in_array($data['type'], ['TextArea', 'TextInput'], true)) {
            foreach ($data['values'] as $value) {
                $rozetkaId = (string) $value['rozetkaId'];
                $title = (string) $value['title'];
                $characteristicsValue = $this->valueRepository->findOneBy(['rozetkaId' => $rozetkaId]);
                if ($characteristicsValue || $title === '') {
                    continue;
                }
                $characteristicsValue = new RozetkaCharacteristicsValue();
                $characteristicsValue->setRozetkaId($rozetkaId);
                $characteristicsValue->setTitle($title);
                $characteristicsValue->setActive((bool) $value['active']);

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

    public function update(RozetkaCharacteristics $characteristics): void
    {
        $this->getEntityManager()->flush();
    }

    public function getCharacteristicsForCategory(Category $category): mixed
    {
        return $this->createQueryBuilder('char')
            ->where(':category MEMBER OF char.categories')
            ->setParameter('category', $category)
            ->andWhere('char.active = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();
    }
}
