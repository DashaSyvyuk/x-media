<?php

namespace App\Repository;

use App\Entity\DebtorPayment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DebtorPayment|null find($id, $lockMode = null, $lockVersion = null)
 * @method DebtorPayment|null findOneBy(array $criteria, array $orderBy = null)
 * @method DebtorPayment[]    findAll()
 * @method DebtorPayment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DebtorPaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DebtorPayment::class);
    }
}
