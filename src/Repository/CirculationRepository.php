<?php

namespace App\Repository;

use App\Entity\Circulation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Circulation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Circulation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Circulation[]    findAll()
 * @method Circulation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CirculationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Circulation::class);
    }
}
