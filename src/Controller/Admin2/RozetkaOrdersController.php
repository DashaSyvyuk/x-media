<?php

namespace App\Controller\Admin2;

use App\Service\Admin2\OrderClipboardFormatter;
use App\Service\Admin2\RozetkaOrderPresenter;
use App\Service\Admin2\RozetkaSellerApiClient;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_ADMIN')")]
class RozetkaOrdersController extends AbstractController
{
    public function __construct(
        private readonly RozetkaSellerApiClient $apiClient,
        private readonly RozetkaOrderPresenter $presenter,
        private readonly OrderClipboardFormatter $clipboardFormatter,
    ) {
    }

    #[Route('/admin2/rozetka-orders', name: 'admin2_rozetka_orders', methods: ['GET'])]
    public function index(): Response
    {
        $apiError = null;
        $orders = [];
        $counts = null;

        if (! $this->apiClient->isConfigured()) {
            $apiError = 'Додайте ROZETKA_SELLER_USERNAME та ROZETKA_SELLER_PASSWORD у .env.local';
        } else {
            try {
                $counts = $this->apiClient->getCounts();
                foreach ($this->apiClient->fetchActiveOrders() as $apiOrder) {
                    $orders[] = $this->presenter->presentListItem($apiOrder);
                }
            } catch (\Throwable $e) {
                $apiError = $e->getMessage();
            }
        }

        return $this->render('admin2/rozetka_orders/index.html.twig', [
            'orders'     => $orders,
            'counts'     => $counts,
            'apiError'   => $apiError,
            'configured' => $this->apiClient->isConfigured(),
        ]);
    }

    #[Route('/admin2/rozetka-orders/{id}', name: 'admin2_rozetka_orders_show', methods: ['GET'])]
    public function show(int $id): Response
    {
        if (! $this->apiClient->isConfigured()) {
            $this->addFlash('error', 'Rozetka API не налаштовано.');

            return $this->redirectToRoute('admin2_rozetka_orders');
        }

        $apiOrder = $this->apiClient->fetchOrderDetails($id);
        if ($apiOrder === null) {
            throw $this->createNotFoundException('Замовлення Rozetka не знайдено.');
        }

        return $this->render('admin2/rozetka_orders/show.html.twig', [
            'order'    => $this->presenter->presentDetail($apiOrder),
            'raw'      => $apiOrder,
            'copyText' => $this->clipboardFormatter->formatRozetkaOrder($apiOrder),
        ]);
    }

    #[Route('/admin2/rozetka-orders/{id}/update', name: 'admin2_rozetka_orders_update', methods: ['POST'])]
    public function update(Request $request, int $id): Response
    {
        if (! $this->isCsrfTokenValid('rozetka_order_update', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_rozetka_orders_show', ['id' => $id]);
        }

        if (! $this->apiClient->isConfigured()) {
            $this->addFlash('error', 'Rozetka API не налаштовано.');

            return $this->redirectToRoute('admin2_rozetka_orders_show', ['id' => $id]);
        }

        $payload = [];
        $status = $request->request->get('status');
        if ($status !== null && $status !== '') {
            $payload['status'] = (int) $status;
        }

        $ttn = trim((string) $request->request->get('ttn', ''));
        if ($ttn !== '') {
            $payload['ttn'] = $ttn;
        }

        $comment = trim((string) $request->request->get('seller_comment', ''));
        if ($comment !== '') {
            $payload['seller_comment'] = $comment;
        }

        try {
            $this->apiClient->updateOrder($id, $payload);
            $this->addFlash('success', 'Замовлення Rozetka оновлено.');
        } catch (\Throwable $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('admin2_rozetka_orders_show', ['id' => $id]);
    }
}
