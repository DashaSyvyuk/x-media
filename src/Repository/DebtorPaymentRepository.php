<?php

namespace App\Repository;

use App\Entity\DebtorPayment;
use App\Entity\Debtor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DebtorPayment>
 */
class DebtorPaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DebtorPayment::class);
    }

    /**
     * @return list<DebtorPayment>
     */
    public function findByDebtorOrdered(Debtor $debtor): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.debtor = :debtor')
            ->setParameter('debtor', $debtor)
            ->orderBy('p.createdAt', 'DESC')
            ->addOrderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
