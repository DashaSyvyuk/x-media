<?php

namespace App\EventListener;

use App\Entity\Order;
use App\Repository\SettingRepository;
use App\Utils\TurboSms;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Exception;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: Order::class)]
class OrderSubscriber
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly SettingRepository $settingRepository,
        private readonly Environment $twig,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    public function preUpdate(Order $order, PreUpdateEventArgs $args): void
    {
        $changes = $args->getEntityChangeSet();

        if (isset($changes['ttn']) && !empty($changes['ttn'][1])) {
            $old = $changes['ttn'][0];
            $new = $changes['ttn'][1];
            if ($new !== $old) {
                if ($order->getEmail() && $order->getSendNotification()) {
                    $message = (new Email())
                        ->subject(sprintf('Замовлення № %s прямує до Вас', $order->getOrderNumber()))
                        ->from('x-media@x-media.com.ua')
                        ->to($order->getEmail())
                        ->html(
                            $this->twig->render(
                                'emails/client-orders-delivery.html.twig',
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

                if ($order->getPhone() && $order->getSendNotification()) {
                    TurboSms::send($order->getPhone(), sprintf('Ваше замовлення %s відправлено! ТТН: %s', $order->getOrderNumber(), $new));
                }
            }
        }
    }
}
