<?php

namespace App\Repository;

use App\Entity\Comment;
use App\Entity\Product;
use App\Entity\ProductRating;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
}
