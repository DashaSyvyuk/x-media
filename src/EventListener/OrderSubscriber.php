<?php

namespace App\EventListener;

use App\Entity\Order;
use App\Repository\SettingRepository;
use App\Utils\TurboSms;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: Order::class)]
class OrderSubscriber
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly ContainerInterface $container,
        private readonly SettingRepository $settingRepository
    )
    {
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function preUpdate(Order $order, PreUpdateEventArgs $args): void
    {
        $changes = $args->getEntityChangeSet();

        if (isset($changes['ttn']) && !empty($changes['ttn'][1])) {
            $old = $changes['ttn'][0];
            $new = $changes['ttn'][1];
            if ($new !== $old) {
                if ($order->getEmail()) {
                    $message = (new Email())
                        ->subject(sprintf('Замовлення № %s прямує до Вас', $order->getOrderNumber()))
                        ->from('x-media@x-media.com.ua')
                        ->to($order->getEmail())
                        ->html(
                            $this->renderView(
                                [
                                    'name' => $order->getName(),
                                    'orderNumber' => $order->getOrderNumber(),
                                    'ttn' => $new,
                                    'phoneNumber' => $this->settingRepository->findOneBy(['slug' => 'phone_number']),
                                    'email' => $this->settingRepository->findOneBy(['slug' => 'email']),
                                ]
                            )
                        );
                    $this->mailer->send($message);
                }

                if ($phone = $order->getPhone()) {
                    TurboSms::send($phone, sprintf('Ваше замовлення %s відправлено! ТТН: %s', $order->getOrderNumber(), $new));
                }
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
