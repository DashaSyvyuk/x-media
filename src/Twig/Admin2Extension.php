<?php

namespace App\Twig;

use App\Entity\Comment;
use App\Entity\Feedback;
use App\Entity\Order;
use App\Entity\ReturnProduct;
use App\Entity\Warranty;
use App\Repository\AdminPlanRepository;
use App\Repository\CommentRepository;
use App\Repository\FeedbackRepository;
use App\Repository\OrderRepository;
use App\Repository\ReturnProductRepository;
use App\Repository\WarrantyRepository;
use App\Service\Admin2\RozetkaSellerApiClient;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class Admin2Extension extends AbstractExtension
{
    public function __construct(
        private readonly CommentRepository $commentRepository,
        private readonly FeedbackRepository $feedbackRepository,
        private readonly OrderRepository $orderRepository,
        private readonly ReturnProductRepository $returnProductRepository,
        private readonly WarrantyRepository $warrantyRepository,
        private readonly AdminPlanRepository $adminPlanRepository,
        private readonly RozetkaSellerApiClient $rozetkaApiClient,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('admin2_new_comments_count', [$this, 'getNewCommentsCount']),
            new TwigFunction('admin2_new_feedbacks_count', [$this, 'getNewFeedbacksCount']),
            new TwigFunction('admin2_new_orders_count', [$this, 'getNewOrdersCount']),
            new TwigFunction('admin2_new_rozetka_orders_count', [$this, 'getNewRozetkaOrdersCount']),
            new TwigFunction('admin2_new_returns_count', [$this, 'getNewReturnsCount']),
            new TwigFunction('admin2_new_warranties_count', [$this, 'getNewWarrantiesCount']),
            new TwigFunction('admin2_today_plans_count', [$this, 'getTodayPlansCount']),
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
        if (!$this->canSeeOrdersMenu()) {
            return 0;
        }

        return $this->orderRepository->count(['status' => Order::NEW]);
    }

    public function getNewRozetkaOrdersCount(): int
    {
        if (!$this->canSeeOrdersMenu()) {
            return 0;
        }

        return $this->rozetkaApiClient->countNewOrders();
    }

    public function getNewReturnsCount(): int
    {
        if (!$this->canSeeOrdersMenu()) {
            return 0;
        }

        return $this->returnProductRepository->count(['status' => ReturnProduct::STATUS_NEW]);
    }

    public function getNewWarrantiesCount(): int
    {
        if (!$this->canSeeOrdersMenu()) {
            return 0;
        }

        return $this->warrantyRepository->count(['status' => Warranty::STATUS_NEW]);
    }

    public function getTodayPlansCount(): int
    {
        if (!$this->authorizationChecker->isGranted('ROLE_USER')) {
            return 0;
        }

        return $this->adminPlanRepository->countTodayPending(new \DateTimeImmutable('today'));
    }

    private function canSeeOrdersMenu(): bool
    {
        return $this->authorizationChecker->isGranted('ROLE_ADMIN');
    }
}
