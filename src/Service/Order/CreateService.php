<?php

namespace App\Service\Order;

use App\Entity\Order;
use App\Repository\SettingRepository;
use App\Utils\TurboSms;
use Symfony\Component\Mime\Email;
use Twig\Environment;
use Symfony\Component\Mailer\MailerInterface;

class CreateService
{
    public function __construct(
        private readonly Environment $twig,
        private readonly SettingRepository $settingRepository,
        private readonly MailerInterface $mailer
    ) {
    }

    public function run(Order $order, string $host): void
    {
        $managerEmail = $this->settingRepository->findOneBy(['slug' => 'email']);
        if (! $managerEmail) {
            throw new \Exception('Service manager email is missing');
        }
        $mainUrl = sprintf('https://%s/', $host);

        if ($order->getEmail()) {
            $message = (new Email())
                ->subject(sprintf('Нове замовлення %s', $order->getOrderNumber()))
                ->from('x-media@x-media.com.ua')
                ->to($order->getEmail())
                ->html(
                    $this->twig->render(
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
            TurboSms::send(
                $phone,
                sprintf('Дякуємо за замовлення у нашому магазині! Номер замовлення %s :)', $order->getOrderNumber())
            );
        }

        $managerMessage = (new Email())
            ->subject(sprintf('Нове замовлення %s', $order->getOrderNumber()))
            ->from('x-media@x-media.com.ua')
            ->to($managerEmail->getValue())
            ->html(
                $this->twig->render(
                    'emails/manager-order.html.twig',
                    [
                        'mainUrl' => $mainUrl,
                        'order'   => $order
                    ]
                )
            )
        ;

        $this->mailer->send($managerMessage);
    }
}
