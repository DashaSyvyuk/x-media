<?php

namespace App\Controller\Admin2;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Service\Admin2\Admin2Paginator;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_ADMIN')")]
class OrdersController extends AbstractController
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly Admin2Paginator $admin2Paginator,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin/orders', name: 'admin2_orders', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search    = trim((string) $request->query->get('q', ''));
        $status    = (string) $request->query->get('status', '');
        $dateFrom  = $this->parseDate((string) $request->query->get('dateFrom', ''), false);
        $dateTo    = $this->parseDate((string) $request->query->get('dateTo', ''), true);
        $sort      = (string) $request->query->get('sort', 'id');
        $direction = (string) $request->query->get('dir', 'DESC');
        $page      = $request->query->getInt('page', 1);
        $perPage = $this->admin2Paginator->normalizePerPage(
            $request->query->getInt('perPage', Admin2Paginator::DEFAULT_PER_PAGE),
        );

        $query = $this->orderRepository->createAdminListQueryBuilder(
            $search,
            $status !== '' ? $status : null,
            $sort,
            $direction,
            $dateFrom,
            $dateTo,
        );
        $pagination = $this->admin2Paginator->paginate($query, $page, $perPage);

        return $this->render('admin2/orders/index.html.twig', [
            'pagination'     => $pagination,
            'summary'        => $this->orderRepository->getStatusSummary(
                $status !== '' ? $status : null,
                $dateFrom,
                $dateTo,
            ),
            'search'         => $search,
            'status'         => $status,
            'dateFrom'       => $dateFrom?->format('Y-m-d') ?? '',
            'dateTo'         => $dateTo?->format('Y-m-d') ?? '',
            'statusChoices'  => Order::STATUSES,
            'sort'           => $sort,
            'direction'      => strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC',
            'perPage'        => $perPage,
            'perPageOptions' => Admin2Paginator::PER_PAGE_OPTIONS,
        ]);
    }

    #[Route('/admin/orders/{id}/delete', name: 'admin2_orders_delete', methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        if (! $this->isCsrfTokenValid('admin2_order_action', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_orders', $request->query->all());
        }

        $order = $this->orderRepository->find($id);
        if ($order === null) {
            $this->addFlash('error', 'Замовлення не знайдено.');

            return $this->redirectToRoute('admin2_orders', $request->query->all());
        }

        $orderNumber = $order->getOrderNumber();
        $this->entityManager->remove($order);
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('Замовлення #%s видалено.', $orderNumber));

        return $this->redirectToRoute('admin2_orders', $request->query->all());
    }

    private function parseDate(string $value, bool $endOfDay): ?\DateTimeImmutable
    {
        $value = trim($value);
        if ($value === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            $date = new \DateTimeImmutable($value);
        } catch (\Exception) {
            return null;
        }

        return $endOfDay
            ? $date->setTime(23, 59, 59)
            : $date->setTime(0, 0, 0);
    }
}
