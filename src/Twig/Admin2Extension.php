<?php

namespace App\Twig;

use App\Entity\Comment;
use App\Entity\Feedback;
use App\Entity\Order;
use App\Repository\CommentRepository;
use App\Repository\FeedbackRepository;
use App\Repository\OrderRepository;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class Admin2Extension extends AbstractExtension
{
    public function __construct(
        private readonly CommentRepository $commentRepository,
        private readonly FeedbackRepository $feedbackRepository,
        private readonly OrderRepository $orderRepository,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('admin2_new_comments_count', [$this, 'getNewCommentsCount']),
            new TwigFunction('admin2_new_feedbacks_count', [$this, 'getNewFeedbacksCount']),
            new TwigFunction('admin2_new_orders_count', [$this, 'getNewOrdersCount']),
        ];
    }

    public function getNewCommentsCount(): int
    {
        if (!$this->authorizationChecker->isGranted('ROLE_USER')) {
            return 0;
        }

        return $this->commentRepository->count(['status' => Comment::STATUS_NEW]);
    }

    public function getNewFeedbacksCount(): int
    {
        if (!$this->authorizationChecker->isGranted('ROLE_USER')) {
            return 0;
        }

        return $this->feedbackRepository->count(['status' => Feedback::STATUS_NEW]);
    }

    public function getNewOrdersCount(): int
    {
        if (!$this->authorizationChecker->isGranted('ROLE_SUPER_ADMIN')) {
            return 0;
        }

        return $this->orderRepository->count(['status' => Order::NEW]);
    }
}
