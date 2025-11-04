<?php

namespace App\EventListener;

use App\Entity\OrderItem;
use Doctrine\ORM\Events;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Common\EventSubscriber;

class OrderItemSubscriber implements EventSubscriber
{
    public function getSubscribedEvents(): array
    {
        // return the subscribed events, their methods and priorities
        return [
            Events::postPersist,
            Events::postUpdate,
            Events::postRemove,
        ];
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    public function postPersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (! $entity instanceof OrderItem) {
            return;
        }

        $em = $args->getObjectManager();
        $order = $entity->getOrder();
        $total = 0;

        foreach ($order->getItems() as $item) {
            $total += ($item->getPrice() ?: $item->getProduct()->getPrice()) * $item->getCount();
            $item->setPrice($item->getPrice() ?: $item->getProduct()->getPrice());
        }

        $order->setTotal($total);
        $em->flush();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    public function postUpdate(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (! $entity instanceof OrderItem) {
            return;
        }

        $em = $args->getObjectManager();
        $order = $entity->getOrder();
        $total = 0;

        foreach ($order->getItems() as $item) {
            $total += ($item->getPrice() ?: $item->getProduct()->getPrice()) * $item->getCount();
        }

        $order->setTotal($total);
        $em->flush();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    public function postRemove(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (! $entity instanceof OrderItem) {
            return;
        }

        $em = $args->getObjectManager();
        $order = $entity->getOrder();
        $total = 0;

        foreach ($order->getItems() as $item) {
            $total += ($item->getPrice() ?: $item->getProduct()->getPrice()) * $item->getCount();
        }

        $order->setTotal($total);
        $em->flush();
    }
}
