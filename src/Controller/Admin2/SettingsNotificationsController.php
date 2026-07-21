<?php

namespace App\Controller\Admin2;

use App\Entity\AdminPushSubscription;
use App\Entity\AdminUser;
use App\Repository\AdminPushSubscriptionRepository;
use App\Service\Admin2\AdminWebPushNotifier;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_USER')")]
class SettingsNotificationsController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AdminPushSubscriptionRepository $subscriptionRepository,
        private readonly AdminWebPushNotifier $notifier,
    ) {
    }

    #[Route('/admin/settings/notifications/vapid-key', name: 'admin2_settings_notifications_vapid', methods: ['GET'])]
    public function vapidKey(): JsonResponse
    {
        return $this->json([
            'configured' => $this->notifier->isConfigured(),
            'publicKey'  => $this->notifier->getPublicKey(),
        ]);
    }

    #[Route(
        '/admin/settings/notifications/subscribe',
        name: 'admin2_settings_notifications_subscribe',
        methods: ['POST'],
    )]
    public function subscribe(Request $request): JsonResponse
    {
        $csrfToken = (string) $request->headers->get('X-CSRF-TOKEN');
        if (! $this->isCsrfTokenValid('admin2_notifications', $csrfToken)) {
            return $this->json(
                ['ok' => false, 'error' => 'Невірний CSRF-токен.'],
                Response::HTTP_FORBIDDEN,
            );
        }

        if (! $this->notifier->isConfigured()) {
            return $this->json(
                ['ok' => false, 'error' => 'Push не налаштовано на сервері (VAPID).'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        /** @var AdminUser $user */
        $user = $this->getUser();
        $payload = $this->decodeJson($request);

        $endpoint = trim((string) ($payload['endpoint'] ?? ''));
        $p256dh = trim((string) ($payload['keys']['p256dh'] ?? ''));
        $auth = trim((string) ($payload['keys']['auth'] ?? ''));

        if ($endpoint === '' || $p256dh === '' || $auth === '') {
            return $this->json(
                ['ok' => false, 'error' => 'Неповні дані підписки.'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $subscription = $this->subscriptionRepository->findOneByEndpoint($endpoint);
        if (! $subscription instanceof AdminPushSubscription) {
            $subscription = new AdminPushSubscription();
            $subscription->setEndpoint($endpoint);
            $this->entityManager->persist($subscription);
        }

        $subscription->setUser($user);
        $subscription->setP256dh($p256dh);
        $subscription->setAuth($auth);
        $subscription->setUserAgent(mb_substr((string) $request->headers->get('User-Agent'), 0, 512) ?: null);

        $this->entityManager->flush();

        return $this->json([
            'ok'    => true,
            'count' => count($this->subscriptionRepository->findByUser($user)),
        ]);
    }

    #[Route(
        '/admin/settings/notifications/unsubscribe',
        name: 'admin2_settings_notifications_unsubscribe',
        methods: ['POST'],
    )]
    public function unsubscribe(Request $request): JsonResponse
    {
        $csrfToken = (string) $request->headers->get('X-CSRF-TOKEN');
        if (! $this->isCsrfTokenValid('admin2_notifications', $csrfToken)) {
            return $this->json(
                ['ok' => false, 'error' => 'Невірний CSRF-токен.'],
                Response::HTTP_FORBIDDEN,
            );
        }

        /** @var AdminUser $user */
        $user = $this->getUser();
        $payload = $this->decodeJson($request);
        $endpoint = trim((string) ($payload['endpoint'] ?? ''));

        if ($endpoint !== '') {
            $subscription = $this->subscriptionRepository->findOneByEndpoint($endpoint);
            $ownsSubscription = $subscription instanceof AdminPushSubscription
                && $subscription->getUser()->getId() === $user->getId();
            if ($ownsSubscription) {
                $this->entityManager->remove($subscription);
                $this->entityManager->flush();
            }
        } else {
            foreach ($this->subscriptionRepository->findByUser($user) as $subscription) {
                $this->entityManager->remove($subscription);
            }
            $this->entityManager->flush();
        }

        return $this->json([
            'ok'    => true,
            'count' => count($this->subscriptionRepository->findByUser($user)),
        ]);
    }

    #[Route(
        '/admin/settings/notifications/preferences',
        name: 'admin2_settings_notifications_preferences',
        methods: ['POST'],
    )]
    public function preferences(Request $request): Response
    {
        $csrfToken = (string) $request->request->get('_token');
        if (! $this->isCsrfTokenValid('admin2_notifications', $csrfToken)) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_settings', ['tab' => 'notifications']);
        }

        /** @var AdminUser $user */
        $user = $this->getUser();
        $user->setNotifyLocalOrders($request->request->getBoolean('notify_local_orders'));
        $user->setNotifyRozetkaOrders($request->request->getBoolean('notify_rozetka_orders'));
        $this->entityManager->flush();

        $this->addFlash('success', 'Налаштування сповіщень збережено.');

        return $this->redirectToRoute('admin2_settings', ['tab' => 'notifications']);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(Request $request): array
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        return is_array($data) ? $data : [];
    }
}
