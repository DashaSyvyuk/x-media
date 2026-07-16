<?php

namespace App\Controller\Admin2;

use App\Entity\ReturnProduct;
use App\Form\Admin2\ReturnProductType;
use App\Repository\ReturnProductRepository;
use App\Service\Admin2\Admin2Paginator;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_ADMIN')")]
class ReturnsController extends AbstractController
{
    public function __construct(
        private readonly ReturnProductRepository $returnProductRepository,
        private readonly Admin2Paginator $admin2Paginator,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin/returns', name: 'admin2_returns', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search    = trim((string) $request->query->get('q', ''));
        $status    = (string) $request->query->get('status', '');
        $sort      = (string) $request->query->get('sort', 'id');
        $direction = (string) $request->query->get('dir', 'DESC');
        $page      = $request->query->getInt('page', 1);
        $perPage = $this->admin2Paginator->normalizePerPage(
            $request->query->getInt('perPage', Admin2Paginator::DEFAULT_PER_PAGE),
        );

        $query = $this->returnProductRepository->createAdminListQueryBuilder(
            $search,
            $status !== '' ? $status : null,
            $sort,
            $direction,
        );
        $pagination = $this->admin2Paginator->paginate($query, $page, $perPage);

        return $this->render('admin2/returns/index.html.twig', [
            'pagination'     => $pagination,
            'summary'          => $this->returnProductRepository->getStatusSummary($status !== '' ? $status : null),
            'search'         => $search,
            'status'         => $status,
            'statusChoices'  => ReturnProduct::STATUSES,
            'sort'           => $sort,
            'direction'      => strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC',
            'perPage'        => $perPage,
            'perPageOptions' => Admin2Paginator::PER_PAGE_OPTIONS,
            'createForm'     => $this->createForm(ReturnProductType::class, $this->createReturn())->createView(),
        ]);
    }

    #[Route('/admin/returns/new', name: 'admin2_returns_new', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $return = $this->createReturn();
        $form   = $this->createForm(ReturnProductType::class, $return);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($return);
            $this->entityManager->flush();

            $this->addFlash('success', sprintf('Повернення #%d створено.', $return->getId()));

            return $this->redirectToRoute('admin2_returns_edit', ['id' => $return->getId()]);
        }

        $this->addFlash('error', 'Не вдалося створити повернення. Перевірте форму.');

        return $this->redirectToRoute('admin2_returns');
    }

    #[Route('/admin/returns/{id}/delete', name: 'admin2_returns_delete', methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        if (! $this->isCsrfTokenValid('admin2_return_action', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_returns', $request->query->all());
        }

        $return = $this->returnProductRepository->find($id);
        if ($return === null) {
            $this->addFlash('error', 'Повернення не знайдено.');

            return $this->redirectToRoute('admin2_returns', $request->query->all());
        }

        $this->entityManager->remove($return);
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('Повернення #%d видалено.', $id));

        return $this->redirectToRoute('admin2_returns', $request->query->all());
    }

    private function createReturn(): ReturnProduct
    {
        $return = new ReturnProduct();
        $return->setStatus(ReturnProduct::STATUS_NEW);
        $return->setAmount(0);

        return $return;
    }
}
