<?php

namespace App\Service\Order;

use App\Entity\Order;
use App\Repository\AdminUserRepository;
use App\Repository\SettingRepository;
use App\Utils\TurboSms;
use Symfony\Component\Mime\Email;
use Twig\Environment;
use Symfony\Component\Mailer\MailerInterface;

readonly class CreateService
{
    public function __construct(
        private Environment $twig,
        private SettingRepository $settingRepository,
        private AdminUserRepository $adminUserRepository,
        private MailerInterface $mailer,
    ) {
    }

    public function run(Order $order, string $host): void
    {
        $managerEmail = $this->settingRepository->findOneBy(['slug' => 'email']);
        if (! $managerEmail) {
            throw new \Exception('Service manager email is missing');
        }
        $mainUrl = sprintf('https://%s/', $host);

        $adminRecipients = $this->resolveAdminRecipients((string) $managerEmail->getValue());
        if ($adminRecipients === []) {
            throw new \Exception('Service manager email is missing');
        }

        // Notify admins first so a customer-mail failure cannot skip them.
        $managerMessage = (new Email())
            ->subject(sprintf('Нове замовлення %s', $order->getOrderNumber()))
            ->from('x-media@x-media.com.ua')
            ->to(...$adminRecipients)
            ->html(
                $this->twig->render(
                    'emails/manager-order.html.twig',
                    [
                        'mainUrl' => $mainUrl,
                        'order'   => $order,
                    ]
                )
            );

        $this->mailer->send($managerMessage);

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
                            'email' => $managerEmail,
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
    }

    /**
     * Setting "email" plus active AdminUser addresses with notifyLocalOrders.
     *
     * @return list<string>
     */
    private function resolveAdminRecipients(string $settingEmail): array
    {
        $recipients = [];
        $settingEmail = trim($settingEmail);
        if ($settingEmail !== '') {
            $recipients[strtolower($settingEmail)] = $settingEmail;
        }

        foreach ($this->adminUserRepository->findEmailsForLocalOrderNotifications() as $email) {
            $recipients[strtolower($email)] = $email;
        }

        return array_values($recipients);
    }
}
