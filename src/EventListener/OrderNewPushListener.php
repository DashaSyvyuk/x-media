<?php

namespace App\EventListener;

use App\Entity\Order;
use App\Service\Admin2\AdminWebPushNotifier;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;

#[AsEntityListener(event: Events::postPersist, method: 'postPersist', entity: Order::class)]
class OrderNewPushListener
{
    public function __construct(
        private readonly AdminWebPushNotifier $notifier,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function postPersist(Order $order): void
    {
        try {
            $this->notifier->notifyNewLocalOrder($order);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send new-order push notification.', [
                'order_id'  => $order->getId(),
                'exception' => $e,
            ]);
        }
    }
}
