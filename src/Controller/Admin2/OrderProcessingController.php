<?php

namespace App\Controller\Admin2;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Service\Admin2\OrderFulfillmentCustomerBoardProvider;
use App\Service\Admin2\OrderFulfillmentStatusHelper;
use App\Service\Admin2\OrderStatusHelper;
use App\Service\Admin2\RozetkaSellerApiClient;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_USER')")]
class OrderProcessingController extends AbstractController
{
    public function __construct(
        private readonly OrderFulfillmentCustomerBoardProvider $customerBoardProvider,
        private readonly OrderFulfillmentStatusHelper $fulfillmentStatusHelper,
        private readonly OrderRepository $orderRepository,
        private readonly OrderStatusHelper $orderStatusHelper,
        private readonly RozetkaSellerApiClient $rozetkaApiClient,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin/orders/processing', name: 'admin2_orders_processing', methods: ['GET'])]
    public function index(): Response
    {
        $packingOrders = [];
        $newOrders = [];
        $processingOrders = [];

        foreach ($this->customerBoardProvider->getCustomerOrders($this->isGranted('ROLE_SUPER_ADMIN')) as $order) {
            $tone = (string) ($order['statusTone'] ?? '');
            if ($this->fulfillmentStatusHelper->isPackingTone($tone)) {
                $packingOrders[] = $order;
            } elseif ($this->fulfillmentStatusHelper->isNewTone($tone)) {
                $newOrders[] = $order;
            } elseif ($this->fulfillmentStatusHelper->isProcessingTone($tone)) {
                $processingOrders[] = $order;
            }
        }

        return $this->render('admin2/orders/processing.html.twig', [
            'packingOrders'    => $packingOrders,
            'newOrders'        => $newOrders,
            'processingOrders' => $processingOrders,
            'statusFormRoute'  => 'admin2_orders_processing_status',
        ]);
    }

    #[Route('/admin/orders/processing/status', name: 'admin2_orders_processing_status', methods: ['POST'])]
    public function updateStatus(Request $request): Response
    {
        if (! $this->isCsrfTokenValid('fulfillment_action', (string) $request->request->get('_token'))) {
            return $this->statusResponse($request, false, 'Невірний CSRF-токен.');
        }

        $customerType = (string) $request->request->get('customer_type', '');
        $customerId = $request->request->getInt('customer_id');
        $status = trim((string) $request->request->get('status', ''));
        $ttn = trim((string) $request->request->get('ttn', ''));

        if ($customerId <= 0 || $status === '') {
            return $this->statusResponse($request, false, 'Невірні дані для зміни статусу.');
        }

        try {
            $customer = $this->applyStatus(
                $customerType,
                $customerId,
                $status,
                $ttn,
                $request->request->has('ttn'),
            );

            return $this->statusResponse($request, true, 'Замовлення оновлено.', [
                'customer' => $customer,
            ]);
        } catch (\Throwable $e) {
            return $this->statusResponse($request, false, $e->getMessage());
        }
    }

    /**
     * @param array<string, mixed> $extra
     */
    private function statusResponse(
        Request $request,
        bool $ok,
        string $message,
        array $extra = [],
    ): Response {
        if (
            $request->isXmlHttpRequest()
            || str_contains((string) $request->headers->get('Accept', ''), 'application/json')
        ) {
            return new JsonResponse([
                'ok'      => $ok,
                'message' => $message,
                ...$extra,
            ], $ok ? 200 : 400);
        }

        $this->addFlash($ok ? 'success' : 'error', $message);

        return $this->redirectToRoute('admin2_orders_processing');
    }

    /**
     * @return array<string, mixed>
     */
    private function applyStatus(
        string $customerType,
        int $customerId,
        string $status,
        string $ttn,
        bool $hasTtnField,
    ): array {
        if ($customerType === 'local') {
            $order = $this->orderRepository->find($customerId);
            if (! $order instanceof Order) {
                throw new \RuntimeException('Локальне замовлення не знайдено.');
            }

            if ($order->getStatus() !== $status) {
                $this->orderStatusHelper->changeStatus($order, $status);
            }
            if ($hasTtnField) {
                $order->setTtn($ttn);
            }
            $this->entityManager->flush();

            $statusCode = $order->getStatus();
            $ttnValue = trim((string) ($order->getTtn() ?? ''));

            return [
                'type'          => 'local',
                'id'            => $order->getId(),
                'statusCode'    => $statusCode,
                'status'        => Order::STATUSES[$statusCode] ?? $statusCode,
                'statusTone'    => $this->fulfillmentStatusHelper->toneForLocal($statusCode),
                'statusChoices' => $this->orderStatusHelper->getAvailableStatuses($order),
                'ttn'           => $ttnValue,
                'hasTtn'        => $ttnValue !== '',
            ];
        }

        if ($customerType === 'rozetka') {
            if (! $this->rozetkaApiClient->isConfigured()) {
                throw new \RuntimeException('Rozetka API не налаштовано.');
            }

            $statusId = (int) $status;
            $payload = ['status' => $statusId];
            if ($ttn !== '') {
                $payload['ttn'] = $ttn;
            }

            $this->rozetkaApiClient->updateOrder($customerId, $payload);

            return [
                'type'       => 'rozetka',
                'id'         => $customerId,
                'statusId'   => $statusId,
                'statusTone' => $this->fulfillmentStatusHelper->toneForRozetka($statusId),
                'ttn'        => $ttn,
                'hasTtn'     => $ttn !== '',
            ];
        }

        throw new \RuntimeException('Невідомий тип замовлення.');
    }
}
