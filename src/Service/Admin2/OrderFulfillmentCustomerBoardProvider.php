<?php

namespace App\Service\Admin2;

use App\Entity\Order;
use App\Repository\OrderRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class OrderFulfillmentCustomerBoardProvider
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly OrderStatusHelper $orderStatusHelper,
        private readonly RozetkaOrderPresenter $rozetkaPresenter,
        private readonly RozetkaSellerApiClient $rozetkaApiClient,
        private readonly OrderFulfillmentStatusHelper $fulfillmentStatusHelper,
        private readonly OrderClipboardFormatter $clipboardFormatter,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getCustomerOrders(bool $withEditLinks = false): array
    {
        $customerOrders = [];

        foreach ($this->orderRepository->findActiveForFulfillmentBoard() as $order) {
            $presented = $this->rozetkaPresenter->presentLocalOrder($order);
            $statusChoices = $this->orderStatusHelper->getAvailableStatuses($order);
            $currentStatus = $order->getStatus();
            if (! isset($statusChoices[$currentStatus]) && isset(Order::STATUSES[$currentStatus])) {
                $statusChoices = [$currentStatus => Order::STATUSES[$currentStatus]] + $statusChoices;
            }

            $presented['statusCode'] = $currentStatus;
            $presented['statusChoices'] = $statusChoices;
            $presented['canEditStatus'] = true;
            $presented['copyText'] = $this->clipboardFormatter->formatLocalOrder($order);
            $presented['statusTone'] = $this->fulfillmentStatusHelper->toneForLocal($currentStatus);
            $presented['editUrl'] = $withEditLinks && $this->authorizationChecker->isGranted('ROLE_SUPER_ADMIN')
                ? $this->urlGenerator->generate('admin2_orders_edit', ['id' => $order->getId()])
                : null;

            $customerOrders[] = $presented;
        }

        if ($this->rozetkaApiClient->isConfigured()) {
            try {
                foreach ($this->rozetkaApiClient->fetchActiveOrders() as $apiOrder) {
                    try {
                        $presented = $this->presentRozetkaBoardOrder($apiOrder, $withEditLinks);
                        if (
                            $this->fulfillmentStatusHelper->isRozetkaDelivering(
                                (int) ($presented['statusId'] ?? 0),
                                (string) ($presented['ttn'] ?? ''),
                            )
                        ) {
                            continue;
                        }
                        $customerOrders[] = $presented;
                    } catch (\Throwable $e) {
                        $this->logger->error('Rozetka fulfillment card failed.', [
                            'rozetkaOrderId' => $apiOrder['id'] ?? null,
                            'exception'      => $e,
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                $this->logger->error('Rozetka orders fetch failed on fulfillment board.', [
                    'exception' => $e,
                ]);
            }
        }

        usort($customerOrders, static fn (array $a, array $b): int => strcmp($b['created'] ?? '', $a['created'] ?? ''));

        return $customerOrders;
    }

    /**
     * @param array<string, mixed> $apiOrder
     *
     * @return array<string, mixed>
     */
    private function presentRozetkaBoardOrder(array $apiOrder, bool $withEditLinks): array
    {
        $detail = $this->rozetkaPresenter->presentBoardItem($apiOrder);
        $detail['editUrl'] = $withEditLinks && $this->authorizationChecker->isGranted('ROLE_SUPER_ADMIN')
            ? $this->urlGenerator->generate('admin2_rozetka_orders_show', ['id' => $detail['id']])
            : null;
        $detail['statusTone'] = $this->fulfillmentStatusHelper->toneForRozetka((int) ($detail['statusId'] ?? 0));

        try {
            $detail['copyText'] = $this->clipboardFormatter->formatRozetkaOrder($apiOrder);
        } catch (\Throwable $e) {
            $this->logger->warning('Rozetka copy text generation failed.', [
                'rozetkaOrderId' => $apiOrder['id'] ?? null,
                'exception'      => $e,
            ]);
            $detail['copyText'] = '';
        }

        return $detail;
    }
}
