<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\ProductRating;
use App\Repository\CommentRepository;
use App\Repository\ProductRatingRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class CommentController extends AbstractController
{
    private CommentRepository $commentRepository;

    private ProductRatingRepository $productRatingRepository;

    private ProductRepository $productRepository;

    /**
     * @param CommentRepository $commentRepository
     * @param ProductRatingRepository $productRatingRepository
     * @param ProductRepository $productRepository
     */
    public function __construct(
        CommentRepository $commentRepository,
        ProductRatingRepository $productRatingRepository,
        ProductRepository $productRepository
    ) {
        $this->commentRepository = $commentRepository;
        $this->productRatingRepository = $productRatingRepository;
        $this->productRepository = $productRepository;
    }

    public function post(Request $request): array
    {
        $product = $this->productRepository->findOneBy(['id' => $request->request->get('product')]);

        $rating = new ProductRating();
        $rating->setProduct($product);
        $rating->setValue($request->request->get('rating'));

        $comment = new Comment();
        $comment->setAuthor($request->request->get('author'));
        $comment->setEmail($request->request->get('email'));
        $comment->setComment($request->request->get('comment'));
        $comment->setProduct($product);
        $comment->setStatus('NEW');

        $this->productRatingRepository->create($rating);
        $this->commentRepository->create($comment);

        return [
            'success' => true
        ];
    }
}
