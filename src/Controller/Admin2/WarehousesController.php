<?php

namespace App\Controller\Admin2;

use App\Entity\Warehouse;
use App\Form\Admin2\WarehouseType;
use App\Repository\WarehouseRepository;
use App\Service\Admin2\Admin2Paginator;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_ADMIN')")]
class WarehousesController extends AbstractController
{
    public function __construct(
        private readonly WarehouseRepository $warehouseRepository,
        private readonly Admin2Paginator $admin2Paginator,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin2/warehouses', name: 'admin2_warehouses', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search    = trim((string) $request->query->get('q', ''));
        $activeRaw = $request->query->has('active') ? (string) $request->query->get('active') : '';
        $active    = $activeRaw === '' ? null : $activeRaw === '1';
        $sort      = (string) $request->query->get('sort', 'title');
        $direction = (string) $request->query->get('dir', 'ASC');
        $page      = $request->query->getInt('page', 1);
        $perPage   = $this->admin2Paginator->normalizePerPage($request->query->getInt('perPage', Admin2Paginator::DEFAULT_PER_PAGE));

        $query = $this->warehouseRepository->createAdminListQueryBuilder($search, $active, $sort, $direction);
        $pagination = $this->admin2Paginator->paginate($query, $page, $perPage);

        $warehouseIds = [];
        foreach ($pagination as $warehouse) {
            $warehouseIds[] = $warehouse->getId();
        }

        return $this->render('admin2/warehouses/index.html.twig', [
            'pagination'     => $pagination,
            'stockTotals'      => $this->warehouseRepository->getStockQuantitySumByWarehouseIds($warehouseIds),
            'search'         => $search,
            'activeFilter'   => $activeRaw,
            'sort'           => $sort,
            'direction'      => strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC',
            'perPage'        => $perPage,
            'perPageOptions' => Admin2Paginator::PER_PAGE_OPTIONS,
        ]);
    }

    #[Route('/admin2/warehouses/new', name: 'admin2_warehouses_new', methods: ['GET', 'POST'])]
    public function createNew(Request $request): Response
    {
        $warehouse = new Warehouse();
        $warehouse->setActive(true);

        return $this->handleForm($request, $warehouse, true);
    }

    #[Route('/admin2/warehouses/{id}/edit', name: 'admin2_warehouses_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $warehouse = $this->warehouseRepository->find($id);
        if (! $warehouse instanceof Warehouse) {
            throw $this->createNotFoundException('Склад не знайдено.');
        }

        return $this->handleForm($request, $warehouse, false);
    }

    private function handleForm(Request $request, Warehouse $warehouse, bool $isNew): Response
    {
        $form = $this->createForm(WarehouseType::class, $warehouse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($isNew) {
                $this->entityManager->persist($warehouse);
            }
            $this->entityManager->flush();

            $this->addFlash('success', sprintf('Склад «%s» збережено.', $warehouse->getTitle()));

            return $this->redirectToRoute('admin2_warehouses_edit', ['id' => $warehouse->getId()]);
        }

        return $this->render('admin2/warehouses/edit.html.twig', [
            'warehouse' => $warehouse,
            'form'      => $form,
            'isNew'     => $isNew,
        ]);
    }
}
