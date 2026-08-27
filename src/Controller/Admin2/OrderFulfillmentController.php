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

#[Security("is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_ADMIN')")]
class OrderFulfillmentController extends AbstractController
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly VendorOrderRepository $vendorOrderRepository,
        private readonly OrderFulfillmentLinkRepository $linkRepository,
        private readonly OrderFulfillmentService $fulfillmentService,
        private readonly OrderFulfillmentCustomerBoardProvider $customerBoardProvider,
        private readonly OrderFulfillmentStatusHelper $fulfillmentStatusHelper,
        private readonly OrderStatusHelper $orderStatusHelper,
        private readonly RozetkaSellerApiClient $rozetkaApiClient,
        private readonly OrderClipboardFormatter $clipboardFormatter,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin/fulfillment', name: 'admin2_fulfillment', methods: ['GET'])]
    public function index(): Response
    {
        $linkColors = $this->linkRepository->buildLinkColorMap();
        $customerOrders = array_values(array_filter(
            $this->customerBoardProvider->getCustomerOrders(true),
            fn (array $order): bool => $this->fulfillmentStatusHelper->isCustomerManagementTone(
                (string) ($order['statusTone'] ?? ''),
            ),
        ));

        usort(
            $customerOrders,
            function (array $a, array $b) use ($linkColors): int {
                $aLinked = isset($linkColors[(string) ($a['key'] ?? '')]);
                $bLinked = isset($linkColors[(string) ($b['key'] ?? '')]);
                $byLinked = ((int) $aLinked) <=> ((int) $bLinked);
                if ($byLinked !== 0) {
                    return $byLinked;
                }

                $byStatus = $this->fulfillmentStatusHelper->sortRankForTone((string) ($a['statusTone'] ?? 'default'))
                    <=> $this->fulfillmentStatusHelper->sortRankForTone((string) ($b['statusTone'] ?? 'default'));
                if ($byStatus !== 0) {
                    return $byStatus;
                }

                return strcmp((string) ($b['created'] ?? ''), (string) ($a['created'] ?? ''));
            },
        );

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

        usort(
            $vendorOrders,
            static function (array $a, array $b): int {
                $byLinked = ((bool) ($a['isLinked'] ?? false) <=> (bool) ($b['isLinked'] ?? false));
                if ($byLinked !== 0) {
                    return $byLinked;
                }

                $rank = static fn (string $status): int => match ($status) {
                    VendorOrder::STATUS_NEW => 0,
                    VendorOrder::STATUS_WAITING_PICKUP => 1,
                    default => 9,
                };

                $byStatus = $rank((string) ($a['statusCode'] ?? '')) <=> $rank((string) ($b['statusCode'] ?? ''));
                if ($byStatus !== 0) {
                    return $byStatus;
                }

                return strcmp((string) ($b['created'] ?? ''), (string) ($a['created'] ?? ''));
            },
        );

        return $this->render('admin2/fulfillment/index.html.twig', [
            'customerOrders'  => $customerOrders,
            'vendorOrders'    => $vendorOrders,
            'linkColors'      => $linkColors,
            'linkPeerMap'     => $this->linkRepository->buildLinkPeerMap(),
            'statusFormRoute' => 'admin2_fulfillment_customer_status',
        ]);
    }

    #[Route('/admin/fulfillment/link', name: 'admin2_fulfillment_link', methods: ['POST'])]
    public function link(Request $request): Response
    {
        if (! $this->isCsrfTokenValid('fulfillment_action', (string) $request->request->get('_token'))) {
            return $this->actionResponse($request, false, 'Невірний CSRF-токен.');
        }

        $vendorOrderId = $request->request->getInt('vendor_order_id');
        $customerType = (string) $request->request->get('customer_type', '');
        $customerId = $request->request->getInt('customer_id');

        $vendorOrder = $this->vendorOrderRepository->find($vendorOrderId);
        if (! $vendorOrder instanceof VendorOrder) {
            return $this->actionResponse($request, false, 'Замовлення постачальника не знайдено.');
        }

        try {
            $linked = null;
            if ($customerType === 'local') {
                $order = $this->orderRepository->find($customerId);
                if (! $order instanceof Order) {
                    throw new \RuntimeException('Локальне замовлення не знайдено.');
                }
                $this->fulfillmentService->linkVendorToOrder($vendorOrder, $order);
                $linked = [
                    'type'  => 'local',
                    'id'    => $order->getId(),
                    'label' => $order->getOrderNumber(),
                    'value' => 'local:' . $order->getId(),
                ];
            } elseif ($customerType === 'rozetka') {
                $this->fulfillmentService->linkVendorToRozetka($vendorOrder, $customerId);
                $linked = [
                    'type'  => 'rozetka',
                    'id'    => $customerId,
                    'label' => 'RZ ' . $customerId,
                    'value' => 'rozetka:' . $customerId,
                ];
            } else {
                throw new \RuntimeException('Невідомий тип замовлення.');
            }

            return $this->actionResponse($request, true, 'Замовлення пов\'язано.', [
                'vendorId' => $vendorOrder->getId(),
                'linked'   => $linked,
                'board'    => $this->boardLinkState(),
            ]);
        } catch (\Throwable $e) {
            return $this->actionResponse($request, false, $e->getMessage());
        }
    }

    #[Route('/admin/fulfillment/customer-status', name: 'admin2_fulfillment_customer_status', methods: ['POST'])]
    public function updateCustomerStatus(Request $request): Response
    {
        if (! $this->isCsrfTokenValid('fulfillment_action', (string) $request->request->get('_token'))) {
            return $this->actionResponse($request, false, 'Невірний CSRF-токен.');
        }

        $customerType = (string) $request->request->get('customer_type', '');
        $customerId = $request->request->getInt('customer_id');
        $status = trim((string) $request->request->get('status', ''));
        $ttn = trim((string) $request->request->get('ttn', ''));

        if ($customerId <= 0 || $status === '') {
            return $this->actionResponse($request, false, 'Невірні дані для зміни статусу.');
        }

        try {
            $customer = $this->applyCustomerStatus(
                $customerType,
                $customerId,
                $status,
                $ttn,
                $request->request->has('ttn'),
            );

            return $this->actionResponse($request, true, 'Замовлення оновлено.', [
                'customer' => $customer,
            ]);
        } catch (\Throwable $e) {
            return $this->actionResponse($request, false, $e->getMessage());
        }
    }

    #[Route('/admin/fulfillment/unlink', name: 'admin2_fulfillment_unlink', methods: ['POST'])]
    public function unlink(Request $request): Response
    {
        if (! $this->isCsrfTokenValid('fulfillment_action', (string) $request->request->get('_token'))) {
            return $this->actionResponse($request, false, 'Невірний CSRF-токен.');
        }

        $vendorOrderId = $request->request->getInt('vendor_order_id');
        $vendorOrder = $this->vendorOrderRepository->find($vendorOrderId);
        if (! $vendorOrder instanceof VendorOrder) {
            return $this->actionResponse($request, false, 'Замовлення постачальника не знайдено.');
        }

        $customerType = trim((string) $request->request->get('customer_type', ''));
        $customerId = $request->request->getInt('customer_id');
        $unlinked = null;

        if ($customerType !== '' && $customerId > 0) {
            $this->fulfillmentService->unlinkVendorFromCustomer($vendorOrder, $customerType, $customerId);
            $unlinked = [
                'type'  => $customerType,
                'id'    => $customerId,
                'value' => $customerType . ':' . $customerId,
                'label' => $this->customerLabel($customerType, $customerId),
            ];
            $message = 'Прив\'язку знято.';
        } else {
            $this->fulfillmentService->unlinkVendorOrder($vendorOrder);
            $message = 'Усі прив\'язки знято.';
        }

        return $this->actionResponse($request, true, $message, [
            'vendorId' => $vendorOrder->getId(),
            'unlinked' => $unlinked,
            'board'    => $this->boardLinkState(),
        ]);
    }

    #[Route('/admin/fulfillment/vendor/{id}/complete', name: 'admin2_fulfillment_vendor_complete', methods: ['POST'])]
    public function completeVendor(Request $request, int $id): Response
    {
        if (! $this->isCsrfTokenValid('fulfillment_action', (string) $request->request->get('_token'))) {
            return $this->actionResponse($request, false, 'Невірний CSRF-токен.');
        }

        $vendorOrder = $this->vendorOrderRepository->find($id);
        if (! $vendorOrder instanceof VendorOrder) {
            return $this->actionResponse($request, false, 'Замовлення постачальника не знайдено.');
        }

        $linkedBefore = $this->linkRepository->findByVendorOrderId($vendorOrder->getId());
        $updatedCustomers = [];
        foreach ($linkedBefore as $link) {
            if ($link->getOrder() !== null) {
                $updatedCustomers[] = [
                    'type' => 'local',
                    'id'   => $link->getOrder()->getId(),
                ];
            } elseif ($link->getRozetkaOrderId() !== null) {
                $updatedCustomers[] = [
                    'type' => 'rozetka',
                    'id'   => $link->getRozetkaOrderId(),
                ];
            }
        }

        $result = $this->fulfillmentService->completeVendorOrder($vendorOrder);

        $customers = [];
        foreach ($updatedCustomers as $ref) {
            try {
                if ($ref['type'] === 'local') {
                    $order = $this->orderRepository->find($ref['id']);
                    if ($order instanceof Order) {
                        $customers[] = $this->presentLocalCustomerState($order);
                    }
                } elseif ($ref['type'] === 'rozetka') {
                    $customers[] = [
                        'type'       => 'rozetka',
                        'id'         => $ref['id'],
                        'statusId'   => $this->fulfillmentStatusHelper->rozetkaStatusAfterVendorDelivered(),
                        'statusTone' => $this->fulfillmentStatusHelper->toneForRozetka(
                            $this->fulfillmentStatusHelper->rozetkaStatusAfterVendorDelivered(),
                        ),
                    ];
                }
            } catch (\Throwable) {
                // keep going — board can refresh later
            }
        }

        $message = $result['updated'] > 0
            ? sprintf('Замовлення постачальника закрито. Оновлено %d пов\'язаних замовлень.', $result['updated'])
            : 'Замовлення постачальника закрито.';

        return $this->actionResponse($request, true, $message, [
            'removedVendorId'   => $id,
            'updatedCustomers'  => $customers,
            'board'             => $this->boardLinkState(),
            'warnings'          => $result['errors'],
        ]);
    }

    /**
     * @param array<string, mixed> $extra
     */
    private function actionResponse(
        Request $request,
        bool $ok,
        string $message,
        array $extra = [],
    ): Response {
        if ($this->wantsJson($request)) {
            return new JsonResponse([
                'ok'      => $ok,
                'message' => $message,
                ...$extra,
            ], $ok ? 200 : 400);
        }

        $this->addFlash($ok ? 'success' : 'error', $message);
        $warnings = $extra['warnings'] ?? [];
        if (is_array($warnings)) {
            foreach ($warnings as $warning) {
                if (is_string($warning) && $warning !== '') {
                    $this->addFlash('warning', $warning);
                }
            }
        }

        return $this->redirectToRoute('admin2_fulfillment');
    }

    private function wantsJson(Request $request): bool
    {
        return $request->isXmlHttpRequest()
            || str_contains((string) $request->headers->get('Accept', ''), 'application/json');
    }

    /**
     * @return array{linkColors: array<string, string>, linkPeers: array<string, list<string>>}
     */
    private function boardLinkState(): array
    {
        return [
            'linkColors' => $this->linkRepository->buildLinkColorMap(),
            'linkPeers'  => $this->linkRepository->buildLinkPeerMap(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function applyCustomerStatus(
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

            return $this->presentLocalCustomerState($order);
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

    /**
     * @return array<string, mixed>
     */
    private function presentLocalCustomerState(Order $order): array
    {
        $status = $order->getStatus();
        $ttn = trim((string) ($order->getTtn() ?? ''));

        return [
            'type'          => 'local',
            'id'            => $order->getId(),
            'statusCode'    => $status,
            'status'        => Order::STATUSES[$status] ?? $status,
            'statusTone'    => $this->fulfillmentStatusHelper->toneForLocal($status),
            'statusChoices' => $this->orderStatusHelper->getAvailableStatuses($order),
            'ttn'           => $ttn,
            'hasTtn'        => $ttn !== '',
        ];
    }

    private function customerLabel(string $customerType, int $customerId): string
    {
        if ($customerType === 'local') {
            $order = $this->orderRepository->find($customerId);

            return $order instanceof Order ? $order->getOrderNumber() : ('#' . $customerId);
        }

        if ($customerType === 'rozetka') {
            return 'RZ ' . $customerId;
        }

        return (string) $customerId;
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
            'supplierAddress'     => trim((string) ($vendorOrder->getSupplier()->getAddress() ?? '')),
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
