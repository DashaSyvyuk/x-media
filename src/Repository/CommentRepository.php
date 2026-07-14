<?php

namespace App\Repository;

use App\Entity\Comment;
use App\Entity\Product;
use App\Entity\ProductRating;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comment>
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    /**
     * @param array<string, string|Product|ProductRating|null> $data
     */
    public function create(array $data): Comment
    {
        $comment = new Comment();
        $comment->setAuthor($data['author']);
        $comment->setEmail($data['email']);
        $comment->setComment($data['comment']);
        $comment->setProduct($data['product']);
        $comment->setStatus($data['status']);
        $comment->setProductRating($data['productRating']);

        $entityManager = $this->getEntityManager();
        $entityManager->persist($comment);
        $entityManager->flush();

        return $comment;
    }

    public function createAdminListQueryBuilder(
        string $search = '',
        ?string $status = null,
        string $sort = 'id',
        string $direction = 'DESC',
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.product', 'product')
            ->addSelect('product');

        $search = trim($search);
        if ($search !== '') {
            $qb->andWhere(
                'LOWER(c.author) LIKE :search OR LOWER(c.email) LIKE :search OR LOWER(product.title) LIKE :search',
            )->setParameter('search', '%' . mb_strtolower($search) . '%');
        }

        if ($status !== null && $status !== '') {
            $qb->andWhere('c.status = :status')->setParameter('status', $status);
        }

        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';

        if ($sort === 'product') {
            $qb->orderBy('product.title', $direction);
        } else {
            $allowedSorts = ['id', 'author', 'status', 'createdAt'];
            if (! in_array($sort, $allowedSorts, true)) {
                $sort = 'id';
            }
            $qb->orderBy('c.' . $sort, $direction);
        }

        return $qb;
    }
}
