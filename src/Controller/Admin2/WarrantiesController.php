<?php

namespace App\Controller\Admin2;

use App\Entity\Warranty;
use App\Form\Admin2\WarrantyType;
use App\Repository\WarrantyRepository;
use App\Service\Admin2\Admin2Paginator;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_SUPER_ADMIN')")]
class WarrantiesController extends AbstractController
{
    public function __construct(
        private readonly WarrantyRepository $warrantyRepository,
        private readonly Admin2Paginator $admin2Paginator,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin2/warranties', name: 'admin2_warranties', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search    = trim((string) $request->query->get('q', ''));
        $status    = (string) $request->query->get('status', '');
        $sort      = (string) $request->query->get('sort', 'id');
        $direction = (string) $request->query->get('dir', 'DESC');
        $page      = $request->query->getInt('page', 1);
        $perPage   = $this->admin2Paginator->normalizePerPage($request->query->getInt('perPage', Admin2Paginator::DEFAULT_PER_PAGE));

        $query = $this->warrantyRepository->createAdminListQueryBuilder(
            $search,
            $status !== '' ? $status : null,
            $sort,
            $direction,
        );
        $pagination = $this->admin2Paginator->paginate($query, $page, $perPage);

        return $this->render('admin2/warranties/index.html.twig', [
            'pagination'     => $pagination,
            'summary'          => $this->warrantyRepository->getStatusSummary($status !== '' ? $status : null),
            'search'         => $search,
            'status'         => $status,
            'statusChoices'  => Warranty::STATUSES,
            'sort'           => $sort,
            'direction'      => strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC',
            'perPage'        => $perPage,
            'perPageOptions' => Admin2Paginator::PER_PAGE_OPTIONS,
            'createForm'     => $this->createForm(WarrantyType::class, $this->createWarranty())->createView(),
        ]);
    }

    #[Route('/admin2/warranties/new', name: 'admin2_warranties_new', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $warranty = $this->createWarranty();
        $form     = $this->createForm(WarrantyType::class, $warranty);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($warranty);
            $this->entityManager->flush();

            $this->addFlash('success', sprintf('Гарантія #%d створена.', $warranty->getId()));

            return $this->redirectToRoute('admin2_warranties_edit', ['id' => $warranty->getId()]);
        }

        $this->addFlash('error', 'Не вдалося створити гарантію. Перевірте форму.');

        return $this->redirectToRoute('admin2_warranties');
    }

    #[Route('/admin2/warranties/{id}/delete', name: 'admin2_warranties_delete', methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        if (! $this->isCsrfTokenValid('admin2_warranty_action', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_warranties', $request->query->all());
        }

        $warranty = $this->warrantyRepository->find($id);
        if ($warranty === null) {
            $this->addFlash('error', 'Гарантію не знайдено.');

            return $this->redirectToRoute('admin2_warranties', $request->query->all());
        }

        $this->entityManager->remove($warranty);
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('Гарантію #%d видалено.', $id));

        return $this->redirectToRoute('admin2_warranties', $request->query->all());
    }

    private function createWarranty(): Warranty
    {
        $warranty = new Warranty();
        $warranty->setStatus(Warranty::STATUS_NEW);
        $warranty->setExpenses(0);

        return $warranty;
    }
}
