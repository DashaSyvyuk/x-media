<?php

namespace App\EventListener;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class OrderSubscriber implements EventSubscriberInterface
{
    public function __construct(private MailerInterface $mailer)
    {
    }
    // this method can only return the event names; you cannot define a
    // custom method name to execute when each event triggers
    public function getSubscribedEvents(): array
    {
        return [
            Events::postUpdate,
        ];
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Order) {
            return;
        }

        /* $message = (new Email())
            ->subject(sprintf('Нове замовлення %s', $entity->getOrderNumber()))
            ->from('x-media@x-media.com.ua')
            ->to($entity->getEmail())
            ->html(
                $this->renderView(
                    'emails/client-orders.html.twig',
                    [

                    ]
                )
            )
        ;

        $this->mailer->send($message);*/
    }
}
