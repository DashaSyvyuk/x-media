<?php

namespace App\Controller\Admin2;

use App\Entity\InStock;
use App\Repository\InStockRepository;
use App\Repository\WarehouseRepository;
use App\Service\Admin2\Admin2Paginator;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_USER')")]
class InStockController extends AbstractController
{
    public function __construct(
        private readonly InStockRepository $inStockRepository,
        private readonly WarehouseRepository $warehouseRepository,
        private readonly Admin2Paginator $admin2Paginator,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin2/in-stock', name: 'admin2_in_stock', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search      = trim((string) $request->query->get('q', ''));
        $warehouseId = $request->query->getInt('warehouse', 0) ?: null;
        $sort        = (string) $request->query->get('sort', 'warehouse');
        $direction   = (string) $request->query->get('dir', 'ASC');
        $page        = $request->query->getInt('page', 1);
        $perPage     = $this->admin2Paginator->normalizePerPage($request->query->getInt('perPage', Admin2Paginator::DEFAULT_PER_PAGE));

        $query = $this->inStockRepository->createAdminListQueryBuilder(
            $search,
            $warehouseId,
            $sort,
            $direction,
        );

        return $this->render('admin2/in_stock/index.html.twig', [
            'pagination'     => $this->admin2Paginator->paginate($query, $page, $perPage),
            'warehouses'     => $this->warehouseRepository->findBy([], ['title' => 'ASC']),
            'search'         => $search,
            'warehouseId'    => $warehouseId ?? 0,
            'sort'           => $sort,
            'direction'      => strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC',
            'perPage'        => $perPage,
            'perPageOptions' => Admin2Paginator::PER_PAGE_OPTIONS,
        ]);
    }

    #[Route('/admin2/in-stock/{id}/delete', name: 'admin2_in_stock_delete', methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        if (! $this->isCsrfTokenValid('admin2_in_stock_action', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_in_stock', $request->query->all());
        }

        $stock = $this->inStockRepository->find($id);
        if ($stock instanceof InStock) {
            $this->entityManager->remove($stock);
            $this->entityManager->flush();
            $this->addFlash('success', 'Запис наявності видалено.');
        }

        return $this->redirectToRoute('admin2_in_stock', $request->query->all());
    }
}
