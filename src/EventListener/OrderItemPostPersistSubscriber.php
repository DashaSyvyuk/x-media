<?php

namespace App\EventListener;

use App\Entity\OrderItem;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Events;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

#[AsEntityListener(event: Events::postPersist, method: 'postPersist', entity: OrderItem::class)]
class OrderItemPostPersistSubscriber
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    public function postPersist(OrderItem $orderItem, PostPersistEventArgs $args): void
    {
        $em = $args->getEntityManager();
        $order = $orderItem->getOrder();
        $total = 0;

        foreach ($order->getItems() as $item) {
            $total += ($item->getPrice() ?: $item->getProduct()->getPrice()) * $item->getCount();
            $item->setPrice($item->getPrice() ?: $item->getProduct()->getPrice());
        }

        $order->setTotal($total);
        $em->flush();
    }
}
