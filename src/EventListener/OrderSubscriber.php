<?php

namespace App\EventListener;

use App\Entity\Order;
use App\Repository\SettingRepository;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class OrderSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly ContainerInterface $container,
        private readonly SettingRepository $settingRepository
    )
    {
    }
    // this method can only return the event names; you cannot define a
    // custom method name to execute when each event triggers
    public function getSubscribedEvents(): array
    {
        return [
            Events::preUpdate,
        ];
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Order) {
            return;
        }

        $changes = $args->getEntityChangeSet();

        if (isset($changes['ttn']) && !empty($changes['ttn'][1])) {
            $old = $changes['ttn'][0];
            $new = $changes['ttn'][1];
            if ($new !== $old) {
                $message = (new Email())
                    ->subject(sprintf('Замовлення № %s прямує до Вас', $entity->getOrderNumber()))
                    ->from('x-media@x-media.com.ua')
                    ->to($entity->getEmail())
                    ->html(
                        $this->renderView(
                            [
                                'name' => $entity->getName(),
                                'orderNumber' => $entity->getOrderNumber(),
                                'ttn' => $new,
                                'phoneNumber' => $this->settingRepository->findOneBy(['slug' => 'phone_number']),
                                'email' => $this->settingRepository->findOneBy(['slug' => 'email']),
                            ]
                        )
                    );
                $this->mailer->send($message);
            }
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function renderView(array $parameters)
    {
        if (!$this->container->has('twig')) {
            throw new \LogicException('You cannot use the "renderView" method if the Twig Bundle is not available. Try running "composer require symfony/twig-bundle".');
        }

        return $this->container->get('twig')->render('emails/client-orders-delivery.html.twig', $parameters);
    }
}
