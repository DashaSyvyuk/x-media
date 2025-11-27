<?php

namespace App\Service\Comment;

use App\Entity\Comment;
use App\Repository\CommentRepository;
use App\Repository\ProductRatingRepository;
use App\Repository\ProductRepository;

class CommentCreateService
{
    public function __construct(
        private readonly CommentRepository $commentRepository,
        private readonly ProductRatingRepository $productRatingRepository,
        private readonly ProductRepository $productRepository
    ) {
    }

    /**
     * @param array<string, string> $data
     */
    public function run(array $data): Comment
    {
        $product = $this->productRepository->findOneBy(['id' => $data['product']]);

        $productRating = $this->productRatingRepository->create([
            'product' => $product,
            'rating'  => $data['rating']
        ]);

        return $this->commentRepository->create([
            'author'        => $data['author'],
            'email'         => $data['email'],
            'comment'       => $data['comment'],
            'product'       => $product,
            'status'        => Comment::STATUS_NEW,
            'productRating' => $productRating,
        ]);
    }
}
