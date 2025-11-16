<?php

namespace App\Service\Order;

use App\Entity\Order;
use App\Repository\SettingRepository;
use App\Utils\TurboSms;
use Symfony\Component\Mime\Email;
use Psr\Container\ContainerInterface;
use Symfony\Component\Mailer\MailerInterface;

class CreateService
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly SettingRepository $settingRepository,
        private readonly MailerInterface $mailer
    ) {
    }

    public function run(Order $order, string $host): void
    {
        $templating = $this->container->get('twig');
        $managerEmail = $this->settingRepository->findOneBy(['slug' => 'email']);
        $mainUrl = sprintf('https://%s/', $host);

        if ($order->getEmail()) {
            $message = (new Email())
                ->subject(sprintf('Нове замовлення %s', $order->getOrderNumber()))
                ->from('x-media@x-media.com.ua')
                ->to($order->getEmail())
                ->html(
                    $templating->render(
                        'emails/client-orders.html.twig',
                        [
                            'order' => $order,
                            'mainUrl' => $mainUrl,
                            'phoneNumber' => $this->settingRepository->findOneBy(['slug' => 'phone_number']),
                            'email' => $managerEmail
                        ]
                    )
                );

            $this->mailer->send($message);
        }

        if ($phone = $order->getPhone()) {
            TurboSms::send($phone, sprintf('Дякуємо за замовлення у нашому магазині! Номер замовлення %s :)', $order->getOrderNumber()));
        }

        $managerMessage = (new Email())
            ->subject(sprintf('Нове замовлення %s', $order->getOrderNumber()))
            ->from('x-media@x-media.com.ua')
            ->to($managerEmail->getValue())
            ->html(
                $templating->render(
                    'emails/manager-order.html.twig',
                    [
                        'mainUrl' => $mainUrl,
                        'order' => $order
                    ]
                )
            )
        ;

        $this->mailer->send($managerMessage);
    }
}
