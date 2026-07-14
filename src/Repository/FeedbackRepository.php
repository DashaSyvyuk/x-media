<?php

namespace App\Repository;

use App\Entity\Feedback;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Feedback>
 */
class FeedbackRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Feedback::class);
    }

    /**
     * @param array<string, string> $data
     */
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

    public function createAdminListQueryBuilder(
        string $search = '',
        ?string $status = null,
        string $sort = 'id',
        string $direction = 'DESC',
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('f');

        $search = trim($search);
        if ($search !== '') {
            $qb->andWhere('LOWER(f.author) LIKE :search OR LOWER(f.email) LIKE :search')
                ->setParameter('search', '%' . mb_strtolower($search) . '%');
        }

        if ($status !== null && $status !== '') {
            $qb->andWhere('f.status = :status')->setParameter('status', $status);
        }

        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
        $allowedSorts = ['id', 'author', 'status', 'createdAt'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'id';
        }

        $qb->orderBy('f.' . $sort, $direction);

        return $qb;
    }
}
