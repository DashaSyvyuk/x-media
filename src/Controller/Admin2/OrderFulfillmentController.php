<?php

namespace App\Controller\Admin2;

use App\Entity\Order;
use App\Entity\OrderFulfillmentLink;
use App\Entity\VendorOrder;
use App\Repository\OrderFulfillmentLinkRepository;
use App\Repository\OrderRepository;
use App\Repository\VendorOrderRepository;
use App\Service\Admin2\OrderClipboardFormatter;
use App\Service\Admin2\OrderFulfillmentCustomerBoardProvider;
use App\Service\Admin2\OrderFulfillmentService;
use App\Service\Admin2\OrderStatusHelper;
use App\Service\Admin2\RozetkaSellerApiClient;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_ADMIN')")]
class OrderFulfillmentController extends AbstractController
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly VendorOrderRepository $vendorOrderRepository,
        private readonly OrderFulfillmentLinkRepository $linkRepository,
        private readonly OrderFulfillmentService $fulfillmentService,
        private readonly OrderFulfillmentCustomerBoardProvider $customerBoardProvider,
        private readonly OrderStatusHelper $orderStatusHelper,
        private readonly RozetkaSellerApiClient $rozetkaApiClient,
        private readonly OrderClipboardFormatter $clipboardFormatter,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin/fulfillment', name: 'admin2_fulfillment', methods: ['GET'])]
    public function index(): Response
    {
        $customerOrders = $this->customerBoardProvider->getCustomerOrders(true);

        $linksByVendorId = [];
        foreach ($this->linkRepository->findAllLinks() as $link) {
            $linksByVendorId[$link->getVendorOrder()->getId()][] = $link;
        }

        $vendorOrders = [];
        foreach ($this->vendorOrderRepository->findActiveForBoard() as $vendorOrder) {
            $vendorOrders[] = $this->presentVendorOrder(
                $vendorOrder,
                $linksByVendorId[$vendorOrder->getId()] ?? [],
                $customerOrders,
            );
        }

        return $this->render('admin2/fulfillment/index.html.twig', [
            'customerOrders'  => $customerOrders,
            'vendorOrders'    => $vendorOrders,
            'linkColors'      => $this->linkRepository->buildLinkColorMap(),
            'linkPeerMap'     => $this->linkRepository->buildLinkPeerMap(),
            'statusFormRoute' => 'admin2_fulfillment_customer_status',
        ]);
    }

    #[Route('/admin/fulfillment/link', name: 'admin2_fulfillment_link', methods: ['POST'])]
    public function link(Request $request): Response
    {
        if (! $this->isCsrfTokenValid('fulfillment_action', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_fulfillment');
        }

        $vendorOrderId = $request->request->getInt('vendor_order_id');
        $customerType = (string) $request->request->get('customer_type', '');
        $customerId = $request->request->getInt('customer_id');

        $vendorOrder = $this->vendorOrderRepository->find($vendorOrderId);
        if (! $vendorOrder instanceof VendorOrder) {
            $this->addFlash('error', 'Замовлення постачальника не знайдено.');

            return $this->redirectToRoute('admin2_fulfillment');
        }

        try {
            if ($customerType === 'local') {
                $order = $this->orderRepository->find($customerId);
                if (! $order instanceof Order) {
                    throw new \RuntimeException('Локальне замовлення не знайдено.');
                }
                $this->fulfillmentService->linkVendorToOrder($vendorOrder, $order);
            } elseif ($customerType === 'rozetka') {
                $this->fulfillmentService->linkVendorToRozetka($vendorOrder, $customerId);
            } else {
                throw new \RuntimeException('Невідомий тип замовлення.');
            }

            $this->addFlash('success', 'Замовлення пов\'язано.');
        } catch (\Throwable $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('admin2_fulfillment');
    }

    #[Route('/admin/fulfillment/customer-status', name: 'admin2_fulfillment_customer_status', methods: ['POST'])]
    public function updateCustomerStatus(Request $request): Response
    {
        if (! $this->isCsrfTokenValid('fulfillment_action', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_fulfillment');
        }

        $customerType = (string) $request->request->get('customer_type', '');
        $customerId = $request->request->getInt('customer_id');
        $status = trim((string) $request->request->get('status', ''));
        $ttn = trim((string) $request->request->get('ttn', ''));

        if ($customerId <= 0 || $status === '') {
            $this->addFlash('error', 'Невірні дані для зміни статусу.');

            return $this->redirectToRoute('admin2_fulfillment');
        }

        try {
            if ($customerType === 'local') {
                $order = $this->orderRepository->find($customerId);
                if (! $order instanceof Order) {
                    throw new \RuntimeException('Локальне замовлення не знайдено.');
                }

                $this->orderStatusHelper->changeStatus($order, $status);
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

        return $this->redirectToRoute('admin2_fulfillment');
    }

    #[Route('/admin/fulfillment/unlink', name: 'admin2_fulfillment_unlink', methods: ['POST'])]
    public function unlink(Request $request): Response
    {
        if (! $this->isCsrfTokenValid('fulfillment_action', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_fulfillment');
        }

        $vendorOrderId = $request->request->getInt('vendor_order_id');
        $vendorOrder = $this->vendorOrderRepository->find($vendorOrderId);
        if (! $vendorOrder instanceof VendorOrder) {
            $this->addFlash('error', 'Замовлення постачальника не знайдено.');

            return $this->redirectToRoute('admin2_fulfillment');
        }

        $customerType = trim((string) $request->request->get('customer_type', ''));
        $customerId = $request->request->getInt('customer_id');

        if ($customerType !== '' && $customerId > 0) {
            $this->fulfillmentService->unlinkVendorFromCustomer($vendorOrder, $customerType, $customerId);
            $this->addFlash('success', 'Прив\'язку знято.');
        } else {
            $this->fulfillmentService->unlinkVendorOrder($vendorOrder);
            $this->addFlash('success', 'Усі прив\'язки знято.');
        }

        return $this->redirectToRoute('admin2_fulfillment');
    }

    #[Route('/admin/fulfillment/vendor/{id}/complete', name: 'admin2_fulfillment_vendor_complete', methods: ['POST'])]
    public function completeVendor(Request $request, int $id): Response
    {
        if (! $this->isCsrfTokenValid('fulfillment_action', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_fulfillment');
        }

        $vendorOrder = $this->vendorOrderRepository->find($id);
        if ($vendorOrder instanceof VendorOrder) {
            $result = $this->fulfillmentService->completeVendorOrder($vendorOrder);

            if ($result['updated'] > 0) {
                $this->addFlash(
                    'success',
                    sprintf(
                        'Замовлення постачальника закрито. Оновлено %d пов\'язаних замовлень.',
                        $result['updated'],
                    ),
                );
            } else {
                $this->addFlash('success', 'Замовлення постачальника закрито.');
            }

            foreach ($result['errors'] as $error) {
                $this->addFlash('warning', $error);
            }
        }

        return $this->redirectToRoute('admin2_fulfillment');
    }

    /**
     * @param OrderFulfillmentLink[]           $links
     * @param array<int, array<string, mixed>> $customerOrders
     *
     * @return array<string, mixed>
     */
    private function presentVendorOrder(
        VendorOrder $vendorOrder,
        array $links,
        array $customerOrders,
    ): array {
        $linkedCustomers = [];
        $linkedValues = [];

        foreach ($links as $link) {
            if ($link->getOrder() !== null) {
                $value = 'local:' . $link->getOrder()->getId();
                $linkedCustomers[] = [
                    'type'  => 'local',
                    'id'    => $link->getOrder()->getId(),
                    'label' => $link->getOrder()->getOrderNumber(),
                    'value' => $value,
                ];
                $linkedValues[] = $value;
            } elseif ($link->getRozetkaOrderId() !== null) {
                $value = 'rozetka:' . $link->getRozetkaOrderId();
                $linkedCustomers[] = [
                    'type'  => 'rozetka',
                    'id'    => $link->getRozetkaOrderId(),
                    'label' => 'RZ ' . $link->getRozetkaOrderId(),
                    'value' => $value,
                ];
                $linkedValues[] = $value;
            }
        }

        $linkableCustomers = array_values(array_filter(
            $customerOrders,
            static function (array $customer) use ($linkedValues): bool {
                if (($customer['type'] ?? '') === 'local' && ($customer['isRozetka'] ?? false)) {
                    return false;
                }

                $value = ($customer['type'] ?? '') . ':' . ($customer['id'] ?? '');

                return ! in_array($value, $linkedValues, true);
            },
        ));

        return [
            'id'                  => $vendorOrder->getId(),
            'key'                 => 'vendor:' . $vendorOrder->getId(),
            'supplier'            => $vendorOrder->getSupplier()->getTitle(),
            'supplierOrderNumber' => $vendorOrder->getSupplierOrderNumber(),
            'productTitle'        => $vendorOrder->getProductTitle(),
            'items'               => $this->presentVendorItems($vendorOrder),
            'price'               => $vendorOrder->getPrice(),
            'notes'               => $vendorOrder->getNotes(),
            'status'              => VendorOrder::STATUSES[$vendorOrder->getStatus()] ?? $vendorOrder->getStatus(),
            'statusCode'          => $vendorOrder->getStatus(),
            'created'             => $vendorOrder->getCreatedAt()->format('d.m.Y H:i'),
            'editUrl'             => $this->generateUrl('admin2_vendor_orders_edit', ['id' => $vendorOrder->getId()]),
            'copyText'            => $this->clipboardFormatter->formatVendorOrder($vendorOrder),
            'isLinked'            => $linkedCustomers !== [],
            'linkedCustomers'     => $linkedCustomers,
            'linkableCustomers'   => $linkableCustomers,
        ];
    }

    /**
     * @return list<array{name: string, qty: int}>
     */
    private function presentVendorItems(VendorOrder $vendorOrder): array
    {
        $items = [];
        foreach ($vendorOrder->getItems() as $item) {
            $title = trim($item->getTitle());
            if ($title === '') {
                continue;
            }

            $items[] = [
                'name' => $title,
                'qty'  => max(1, $item->getQuantity()),
            ];
        }

        if ($items === [] && trim($vendorOrder->getProductTitle()) !== '') {
            $items[] = [
                'name' => $vendorOrder->getProductTitle(),
                'qty'  => 1,
            ];
        }

        return $items;
    }
}
