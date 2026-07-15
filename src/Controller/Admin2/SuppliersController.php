<?php

namespace App\Controller\Admin2;

use App\Entity\Supplier;
use App\Form\Admin2\SupplierType;
use App\Repository\SupplierRepository;
use App\Service\Admin2\Admin2Paginator;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_USER')")]
class SuppliersController extends AbstractController
{
    public function __construct(
        private readonly SupplierRepository $supplierRepository,
        private readonly Admin2Paginator $admin2Paginator,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin2/suppliers', name: 'admin2_suppliers', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search    = trim((string) $request->query->get('q', ''));
        $activeRaw = $request->query->has('active') ? (string) $request->query->get('active') : '';
        $active    = $activeRaw === '' ? null : $activeRaw === '1';
        $sort      = (string) $request->query->get('sort', 'title');
        $direction = (string) $request->query->get('dir', 'ASC');
        $page      = $request->query->getInt('page', 1);
        $perPage = $this->admin2Paginator->normalizePerPage(
            $request->query->getInt('perPage', Admin2Paginator::DEFAULT_PER_PAGE),
        );

        $query = $this->supplierRepository->createAdminListQueryBuilder($search, $active, $sort, $direction);

        return $this->render('admin2/suppliers/index.html.twig', [
            'pagination'     => $this->admin2Paginator->paginate($query, $page, $perPage),
            'search'         => $search,
            'activeFilter'   => $activeRaw,
            'sort'           => $sort,
            'direction'      => strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC',
            'perPage'        => $perPage,
            'perPageOptions' => Admin2Paginator::PER_PAGE_OPTIONS,
        ]);
    }

    #[Route('/admin2/suppliers/new', name: 'admin2_suppliers_new', methods: ['GET', 'POST'])]
    public function createNew(Request $request): Response
    {
        $supplier = new Supplier();
        $supplier->setActive(true);

        return $this->handleForm($request, $supplier, true);
    }

    #[Route('/admin2/suppliers/{id}/edit', name: 'admin2_suppliers_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $supplier = $this->supplierRepository->find($id);
        if (! $supplier instanceof Supplier) {
            throw $this->createNotFoundException('Постачальника не знайдено.');
        }

        return $this->handleForm($request, $supplier, false);
    }

    private function handleForm(Request $request, Supplier $supplier, bool $isNew): Response
    {
        $form = $this->createForm(SupplierType::class, $supplier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($isNew) {
                $this->entityManager->persist($supplier);
            }
            $this->entityManager->flush();

            $this->addFlash('success', sprintf('Постачальника «%s» збережено.', $supplier->getTitle()));

            return $this->redirectToRoute('admin2_suppliers_edit', ['id' => $supplier->getId()]);
        }

        return $this->render('admin2/suppliers/edit.html.twig', [
            'supplier' => $supplier,
            'form'     => $form,
            'isNew'    => $isNew,
        ]);
    }
}
