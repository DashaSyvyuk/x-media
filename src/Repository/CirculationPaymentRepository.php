<?php

namespace App\Repository;

use App\Entity\CirculationPayment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CirculationPayment|null find($id, $lockMode = null, $lockVersion = null)
 * @method CirculationPayment|null findOneBy(array $criteria, array $orderBy = null)
 * @method CirculationPayment[]    findAll()
 * @method CirculationPayment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CirculationPaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CirculationPayment::class);
    }
}
