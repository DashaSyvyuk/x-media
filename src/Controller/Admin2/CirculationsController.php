<?php

namespace App\Controller\Admin2;

use App\Entity\Circulation;
use App\Entity\Currency;
use App\Form\Admin2\CirculationType;
use App\Repository\CirculationRepository;
use App\Service\Admin2\Admin2Paginator;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_ADMIN')")]
class CirculationsController extends AbstractController
{
    public function __construct(
        private readonly CirculationRepository $circulationRepository,
        private readonly Admin2Paginator $admin2Paginator,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin/circulations', name: 'admin2_circulations', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search    = trim((string) $request->query->get('q', ''));
        $activeRaw = $request->query->has('active')
            ? (string) $request->query->get('active')
            : '1';
        $active    = $activeRaw === '1';
        $sort      = (string) $request->query->get('sort', 'total');
        $direction = (string) $request->query->get('dir', 'DESC');
        $page      = $request->query->getInt('page', 1);
        $perPage = $this->admin2Paginator->normalizePerPage(
            $request->query->getInt('perPage', Admin2Paginator::DEFAULT_PER_PAGE),
        );

        $query = $this->circulationRepository->createAdminListQueryBuilder($search, $active, $sort, $direction);
        $pagination = $this->admin2Paginator->paginate($query, $page, $perPage);

        $circulationIds = [];
        foreach ($pagination as $circulation) {
            $circulationIds[] = $circulation->getId();
        }

        return $this->render('admin2/circulations/index.html.twig', [
            'pagination'     => $pagination,
            'balances'       => $this->circulationRepository->getPaymentTotalsByCirculationIds($circulationIds),
            'summary'        => $this->circulationRepository->getFinanceSummary($active),
            'search'         => $search,
            'activeFilter'   => $activeRaw,
            'sort'           => $sort,
            'direction'      => strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC',
            'perPage'        => $perPage,
            'perPageOptions' => Admin2Paginator::PER_PAGE_OPTIONS,
            'createForm'     => $this->createForm(CirculationType::class, $this->createCirculation())->createView(),
        ]);
    }

    #[Route('/admin/circulations/new', name: 'admin2_circulations_new', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $circulation = $this->createCirculation();
        $form        = $this->createForm(CirculationType::class, $circulation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($circulation);
            $this->entityManager->flush();

            $this->addFlash('success', 'Касу створено.');

            return $this->redirectToRoute('admin2_circulations_show', ['id' => $circulation->getId()]);
        }

        $this->addFlash('error', 'Не вдалося створити касу. Перевірте форму.');

        return $this->redirectToRoute('admin2_circulations');
    }

    #[Route('/admin/circulations/{id}/delete', name: 'admin2_circulations_delete', methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        if (! $this->isCsrfTokenValid('admin2_circulation_action', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_circulations', $request->query->all());
        }

        $circulation = $this->circulationRepository->find($id);
        if ($circulation === null) {
            $this->addFlash('error', 'Касу не знайдено.');

            return $this->redirectToRoute('admin2_circulations', $request->query->all());
        }

        $this->entityManager->remove($circulation);
        $this->entityManager->flush();

        $this->addFlash('success', 'Касу видалено.');

        return $this->redirectToRoute('admin2_circulations', $request->query->all());
    }

    private function createCirculation(): Circulation
    {
        $circulation = new Circulation();
        $circulation->setActive(true);

        $currency = $this->entityManager->getRepository(Currency::class)->findOneBy([], ['id' => 'ASC']);
        if ($currency instanceof Currency) {
            $circulation->setCurrency($currency);
        }

        return $circulation;
    }
}
