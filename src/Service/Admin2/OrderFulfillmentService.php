<?php

namespace App\Service\Admin2;

use App\Entity\Order;
use App\Entity\OrderFulfillmentLink;
use App\Entity\VendorOrder;
use App\Repository\OrderFulfillmentLinkRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final class OrderFulfillmentService
{
    /** @var string[] */
    private const LINK_COLORS = [
        '#0d9488', '#2563eb', '#d97706', '#7c3aed', '#db2777', '#059669', '#dc2626', '#0891b2',
    ];

    public function __construct(
        private readonly OrderFulfillmentLinkRepository $linkRepository,
        private readonly OrderStatusHelper $orderStatusHelper,
        private readonly OrderFulfillmentStatusHelper $fulfillmentStatusHelper,
        private readonly RozetkaSellerApiClient $rozetkaApiClient,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function linkVendorToOrder(VendorOrder $vendorOrder, Order $order): OrderFulfillmentLink
    {
        return $this->createLink($vendorOrder, $order, null);
    }

    public function linkVendorToRozetka(VendorOrder $vendorOrder, int $rozetkaOrderId): OrderFulfillmentLink
    {
        return $this->createLink($vendorOrder, null, $rozetkaOrderId);
    }

    public function unlinkVendorOrder(VendorOrder $vendorOrder): void
    {
        foreach ($this->linkRepository->findByVendorOrderId($vendorOrder->getId()) as $link) {
            $this->entityManager->remove($link);
        }

        $this->entityManager->flush();
    }

    public function unlinkVendorFromCustomer(VendorOrder $vendorOrder, string $customerType, int $customerId): void
    {
        foreach ($this->linkRepository->findByVendorOrderId($vendorOrder->getId()) as $link) {
            if ($customerType === 'local' && $link->getOrder()?->getId() === $customerId) {
                $this->entityManager->remove($link);
            }

            if ($customerType === 'rozetka' && $link->getRozetkaOrderId() === $customerId) {
                $this->entityManager->remove($link);
            }
        }

        $this->entityManager->flush();
    }

    /**
     * @return array{updated: int, errors: string[]}
     */
    public function completeVendorOrder(VendorOrder $vendorOrder): array
    {
        $vendorOrder->setStatus(VendorOrder::STATUS_COMPLETED);

        $updated = 0;
        $errors = [];

        foreach ($this->linkRepository->findByVendorOrderId($vendorOrder->getId()) as $link) {
            $order = $link->getOrder();
            if ($order !== null) {
                try {
                    $this->orderStatusHelper->changeStatus(
                        $order,
                        $this->fulfillmentStatusHelper->localStatusAfterVendorDelivered(),
                    );
                    ++$updated;
                } catch (\Throwable $e) {
                    $errors[] = sprintf(
                        'Замовлення #%s: %s',
                        $order->getOrderNumber(),
                        $e->getMessage(),
                    );
                    $this->logger->warning('Failed to advance local order after vendor delivery.', [
                        'orderId'       => $order->getId(),
                        'vendorOrderId' => $vendorOrder->getId(),
                        'exception'     => $e,
                    ]);
                }
            }

            $rozetkaOrderId = $link->getRozetkaOrderId();
            if ($rozetkaOrderId !== null) {
                if (! $this->rozetkaApiClient->isConfigured()) {
                    $errors[] = sprintf('Rozetka #%d: API не налаштовано.', $rozetkaOrderId);

                    continue;
                }

                try {
                    $this->rozetkaApiClient->updateOrder($rozetkaOrderId, [
                        'status' => $this->fulfillmentStatusHelper->rozetkaStatusAfterVendorDelivered(),
                    ]);
                    ++$updated;
                } catch (\Throwable $e) {
                    $errors[] = sprintf('Rozetka #%d: %s', $rozetkaOrderId, $e->getMessage());
                    $this->logger->warning('Failed to advance Rozetka order after vendor delivery.', [
                        'rozetkaOrderId' => $rozetkaOrderId,
                        'vendorOrderId'  => $vendorOrder->getId(),
                        'exception'      => $e,
                    ]);
                }
            }
        }

        $this->entityManager->flush();

        return [
            'updated' => $updated,
            'errors'  => $errors,
        ];
    }

    private function createLink(VendorOrder $vendorOrder, ?Order $order, ?int $rozetkaOrderId): OrderFulfillmentLink
    {
        foreach ($this->linkRepository->findByVendorOrderId($vendorOrder->getId()) as $existingLink) {
            if ($this->isSameCustomerLink($existingLink, $order, $rozetkaOrderId)) {
                return $existingLink;
            }
        }

        $existingGroup = $this->findExistingGroup($order, $rozetkaOrderId, $vendorOrder);
        $linkGroup = $existingGroup ?? $this->generateLinkGroup();

        $link = new OrderFulfillmentLink();
        $link->setLinkGroup($linkGroup);
        $link->setVendorOrder($vendorOrder);
        $link->setOrder($order);
        $link->setRozetkaOrderId($rozetkaOrderId);

        $this->entityManager->persist($link);
        $this->entityManager->flush();

        return $link;
    }

    private function findExistingGroup(?Order $order, ?int $rozetkaOrderId, VendorOrder $vendorOrder): ?string
    {
        foreach ($this->linkRepository->findAllLinks() as $link) {
            if ($link->getVendorOrder()->getId() === $vendorOrder->getId()) {
                return $link->getLinkGroup();
            }

            if ($order !== null && $link->getOrder()?->getId() === $order->getId()) {
                return $link->getLinkGroup();
            }

            if ($rozetkaOrderId !== null && $link->getRozetkaOrderId() === $rozetkaOrderId) {
                return $link->getLinkGroup();
            }
        }

        return null;
    }

    private function isSameCustomerLink(OrderFulfillmentLink $link, ?Order $order, ?int $rozetkaOrderId): bool
    {
        if ($order !== null) {
            return $link->getOrder()?->getId() === $order->getId();
        }

        if ($rozetkaOrderId !== null) {
            return $link->getRozetkaOrderId() === $rozetkaOrderId;
        }

        return false;
    }

    private function generateLinkGroup(): string
    {
        return substr(bin2hex(random_bytes(4)), 0, 8);
    }

    public function colorForGroup(string $linkGroup): string
    {
        $index = abs(crc32($linkGroup)) % count(self::LINK_COLORS);

        return self::LINK_COLORS[$index];
    }
}
