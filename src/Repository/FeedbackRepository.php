<?php

namespace App\Repository;

use App\Entity\Feedback;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Feedback|null find($id, $lockMode = null, $lockVersion = null)
 * @method Feedback|null findOneBy(array $criteria, array $orderBy = null)
 * @method Feedback[]    findAll()
 * @method Feedback[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FeedbackRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Feedback::class);
    }

    public function create(array $data): Feedback
    {
        $feedback = new Feedback();
        $feedback->setAuthor($data['author']);
        $feedback->setEmail($data['email']);
        $feedback->setComment($data['comment']);
        $feedback->setStatus($data['status']);

        $entityManager = $this->getEntityManager();
        $entityManager->persist($feedback);
        $entityManager->flush();

        return $feedback;
    }

    public function findActiveFeedbacks(): QueryBuilder
    {
         return $this
             ->createQueryBuilder('f')
             ->where('f.status = :status')
             ->setParameter('status', 'CONFIRMED')
             ->orderBy('f.createdAt', 'DESC');

    }
}
