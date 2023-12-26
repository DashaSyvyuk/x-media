<?php

namespace App\EventListener;

use App\Entity\OrderItem;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: OrderItem::class)]
class OrderItemSubscriber
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    public function postUpdate(OrderItem $orderItem, PostUpdateEventArgs $args): void
    {
        $em = $args->getEntityManager();
        $order = $orderItem->getOrder();
        $total = 0;

        foreach ($order->getItems() as $item) {
            $total += ($item->getPrice() ?: $item->getProduct()->getPrice()) * $item->getCount();
        }

        $order->setTotal($total);
        $em->flush();
    }
}
