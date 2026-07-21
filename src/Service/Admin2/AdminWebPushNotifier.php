<?php

namespace App\Service\Admin2;

use App\Entity\AdminPushSubscription;
use App\Entity\Order;
use App\Repository\AdminPushSubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class AdminWebPushNotifier
{
    private ?WebPush $webPush = null;

    public function __construct(
        private readonly AdminPushSubscriptionRepository $subscriptionRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly LoggerInterface $logger,
        private readonly string $vapidPublicKey = '',
        private readonly string $vapidPrivateKey = '',
        private readonly string $vapidSubject = 'mailto:x-media@x-media.com.ua',
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->vapidPublicKey !== '' && $this->vapidPrivateKey !== '';
    }

    public function getPublicKey(): string
    {
        return $this->vapidPublicKey;
    }

    public function notifyNewLocalOrder(Order $order): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        $customer = trim(sprintf('%s %s', $order->getName(), $order->getSurname() ?? ''));
        $bodyParts = array_filter([
            '№' . $order->getOrderNumber(),
            $customer !== '' ? $customer : null,
            $this->formatMoney($order->getTotal()),
            $order->getPhone() !== '' ? $order->getPhone() : null,
        ]);

        $this->sendToSubscriptions(
            $this->subscriptionRepository->findForLocalOrderNotifications(),
            'Нове замовлення',
            implode(' · ', $bodyParts),
            $this->urlGenerator->generate(
                'admin2_orders_edit',
                ['id' => $order->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL,
            ),
            'local-order-' . $order->getId(),
        );
    }

    /**
     * @param array{
     *     id: int,
     *     recipient?: string,
     *     phone?: string,
     *     total?: int
     * } $order
     */
    public function notifyNewRozetkaOrder(array $order): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        $id = $order['id'];
        if ($id <= 0) {
            return;
        }

        $bodyParts = array_filter([
            '№' . $id,
            trim((string) ($order['recipient'] ?? '')) ?: null,
            isset($order['total']) ? $this->formatMoney((int) $order['total']) : null,
            trim((string) ($order['phone'] ?? '')) ?: null,
        ]);

        $this->sendToSubscriptions(
            $this->subscriptionRepository->findForRozetkaOrderNotifications(),
            'Нове замовлення Rozetka',
            implode(' · ', $bodyParts),
            $this->urlGenerator->generate(
                'admin2_rozetka_orders_show',
                ['id' => $id],
                UrlGeneratorInterface::ABSOLUTE_URL,
            ),
            'rozetka-order-' . $id,
        );
    }

    /**
     * @param list<AdminPushSubscription> $subscriptions
     */
    private function sendToSubscriptions(
        array $subscriptions,
        string $title,
        string $body,
        string $url,
        string $tag,
    ): void {
        if ($subscriptions === []) {
            return;
        }

        $payload = json_encode([
            'title' => $title,
            'body'  => $body,
            'url'   => $url,
            'tag'   => $tag,
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $webPush = $this->getWebPush();
        $stale = [];

        foreach ($subscriptions as $subscription) {
            try {
                $webPush->queueNotification(
                    Subscription::create([
                        'endpoint' => $subscription->getEndpoint(),
                        'keys'     => [
                            'p256dh' => $subscription->getP256dh(),
                            'auth'   => $subscription->getAuth(),
                        ],
                    ]),
                    $payload,
                );
            } catch (\Throwable $e) {
                $this->logger->warning('Failed to queue admin push notification.', [
                    'subscription_id' => $subscription->getId(),
                    'exception'       => $e,
                ]);
            }
        }

        foreach ($webPush->flush() as $report) {
            if ($report->isSuccess()) {
                continue;
            }

            $endpoint = $report->getEndpoint();
            $reason = $report->getReason();
            $this->logger->info('Admin push delivery failed.', [
                'endpoint' => $endpoint,
                'reason'   => $reason,
            ]);

            if ($report->isSubscriptionExpired()) {
                $stale[] = $endpoint;
            }
        }

        foreach (array_unique($stale) as $endpoint) {
            $entity = $this->subscriptionRepository->findOneByEndpoint($endpoint);
            if ($entity instanceof AdminPushSubscription) {
                $this->entityManager->remove($entity);
            }
        }

        if ($stale !== []) {
            $this->entityManager->flush();
        }
    }

    private function getWebPush(): WebPush
    {
        if ($this->webPush instanceof WebPush) {
            return $this->webPush;
        }

        $this->webPush = new WebPush([
            'VAPID' => [
                'subject'    => $this->vapidSubject,
                'publicKey'  => $this->vapidPublicKey,
                'privateKey' => $this->vapidPrivateKey,
            ],
        ]);
        $this->webPush->setReuseVAPIDHeaders(true);

        return $this->webPush;
    }

    private function formatMoney(int $amount): string
    {
        return number_format($amount, 0, '.', ' ') . ' грн';
    }
}
