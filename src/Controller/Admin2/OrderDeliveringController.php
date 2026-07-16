<?php

namespace App\Controller\Admin2;

use App\Repository\OrderRepository;
use App\Service\Admin2\OrderFulfillmentStatusHelper;
use App\Service\Admin2\RozetkaOrderPresenter;
use App\Service\Admin2\RozetkaSellerApiClient;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_USER')")]
class OrderDeliveringController extends AbstractController
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly RozetkaOrderPresenter $rozetkaPresenter,
        private readonly RozetkaSellerApiClient $rozetkaApiClient,
        private readonly OrderFulfillmentStatusHelper $fulfillmentStatusHelper,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route('/admin/orders/delivering', name: 'admin2_orders_delivering', methods: ['GET'])]
    public function index(): Response
    {
        $courierOrders = [];
        $novaPoshtaOrders = [];

        foreach ($this->orderRepository->findShippingOrders() as $order) {
            $presented = $this->rozetkaPresenter->presentLocalOrder($order);
            $presented['statusCode'] = $order->getStatus();
            $presented['statusTone'] = $this->fulfillmentStatusHelper->toneForLocal($order->getStatus());

            if ($this->fulfillmentStatusHelper->isLocalCourierDelivering($order->getStatus())) {
                $courierOrders[] = $presented;
            } elseif ($this->fulfillmentStatusHelper->isLocalNovaPoshtaDelivering($order->getStatus())) {
                $novaPoshtaOrders[] = $presented;
            }
        }

        if ($this->rozetkaApiClient->isConfigured()) {
            try {
                foreach ($this->rozetkaApiClient->fetchActiveOrders() as $apiOrder) {
                    try {
                        $presented = $this->rozetkaPresenter->presentBoardItem($apiOrder);
                        $statusId = (int) ($presented['statusId'] ?? 0);
                        $ttn = (string) ($presented['ttn'] ?? '');
                        if (! $this->fulfillmentStatusHelper->isRozetkaCarrierTransfer($statusId, $ttn)) {
                            continue;
                        }
                        $presented['statusTone'] = $this->fulfillmentStatusHelper->toneForRozetka($statusId);
                        $novaPoshtaOrders[] = $presented;
                    } catch (\Throwable $e) {
                        $this->logger->error('Rozetka delivering card failed.', [
                            'rozetkaOrderId' => $apiOrder['id'] ?? null,
                            'exception'      => $e,
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                $this->logger->error('Rozetka orders fetch failed on delivering board.', [
                    'exception' => $e,
                ]);
            }
        }

        usort(
            $courierOrders,
            static fn (array $a, array $b): int => strcmp((string) ($b['created'] ?? ''), (string) ($a['created'] ?? '')),
        );
        usort(
            $novaPoshtaOrders,
            static fn (array $a, array $b): int => strcmp((string) ($b['created'] ?? ''), (string) ($a['created'] ?? '')),
        );

        return $this->render('admin2/orders/delivering.html.twig', [
            'courierOrders'    => $courierOrders,
            'novaPoshtaOrders' => $novaPoshtaOrders,
        ]);
    }
}
