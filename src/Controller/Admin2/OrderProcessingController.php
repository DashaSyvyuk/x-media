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
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_orders_processing');
        }

        $customerType = (string) $request->request->get('customer_type', '');
        $customerId = $request->request->getInt('customer_id');
        $status = trim((string) $request->request->get('status', ''));
        $ttn = trim((string) $request->request->get('ttn', ''));

        if ($customerId <= 0 || $status === '') {
            $this->addFlash('error', 'Невірні дані для зміни статусу.');

            return $this->redirectToRoute('admin2_orders_processing');
        }

        try {
            if ($customerType === 'local') {
                $order = $this->orderRepository->find($customerId);
                if (! $order instanceof Order) {
                    throw new \RuntimeException('Локальне замовлення не знайдено.');
                }

                if ($order->getStatus() !== $status) {
                    $this->orderStatusHelper->changeStatus($order, $status);
                }
                if ($request->request->has('ttn')) {
                    $order->setTtn($ttn);
                }
                $this->entityManager->flush();
            } elseif ($customerType === 'rozetka') {
                if (! $this->rozetkaApiClient->isConfigured()) {
                    throw new \RuntimeException('Rozetka API не налаштовано.');
                }

                $payload = ['status' => (int) $status];
                if ($ttn !== '') {
                    $payload['ttn'] = $ttn;
                }

                $this->rozetkaApiClient->updateOrder($customerId, $payload);
            } else {
                throw new \RuntimeException('Невідомий тип замовлення.');
            }

            $this->addFlash('success', 'Замовлення оновлено.');
        } catch (\Throwable $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('admin2_orders_processing');
    }
}
