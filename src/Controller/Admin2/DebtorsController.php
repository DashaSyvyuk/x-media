<?php

namespace App\Controller\Admin2;

use App\Entity\Currency;
use App\Entity\Debtor;
use App\Form\Admin2\DebtorType;
use App\Repository\DebtorRepository;
use App\Service\Admin2\Admin2Paginator;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_SUPER_ADMIN')")]
class DebtorsController extends AbstractController
{
    public function __construct(
        private readonly DebtorRepository $debtorRepository,
        private readonly Admin2Paginator $admin2Paginator,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin2/debtors', name: 'admin2_debtors', methods: ['GET'])]
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

        $query = $this->debtorRepository->createAdminListQueryBuilder($search, $active, $sort, $direction);
        $pagination = $this->admin2Paginator->paginate($query, $page, $perPage);

        $debtorIds = [];
        foreach ($pagination as $debtor) {
            $debtorIds[] = $debtor->getId();
        }

        return $this->render('admin2/debtors/index.html.twig', [
            'pagination'     => $pagination,
            'balances'       => $this->debtorRepository->getPaymentTotalsByDebtorIds($debtorIds),
            'summary'          => $this->debtorRepository->getFinanceSummary($active),
            'search'         => $search,
            'activeFilter'   => $activeRaw,
            'sort'           => $sort,
            'direction'      => strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC',
            'perPage'        => $perPage,
            'perPageOptions' => Admin2Paginator::PER_PAGE_OPTIONS,
            'currencies'     => $this->entityManager->getRepository(Currency::class)->findBy([], ['title' => 'ASC']),
            'createForm'     => $this->createForm(DebtorType::class, $this->createDebtor())->createView(),
        ]);
    }

    #[Route('/admin2/debtors/new', name: 'admin2_debtors_new', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $debtor = $this->createDebtor();
        $form   = $this->createForm(DebtorType::class, $debtor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($debtor);
            $this->entityManager->flush();

            $this->addFlash('success', sprintf('Контакт «%s» створено.', $debtor->getName()));

            return $this->redirectToRoute('admin2_debtors_show', ['id' => $debtor->getId()]);
        }

        $this->addFlash('error', 'Не вдалося створити контакт. Перевірте форму.');

        return $this->redirectToRoute('admin2_debtors');
    }

    #[Route('/admin2/debtors/{id}/delete', name: 'admin2_debtors_delete', methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        if (! $this->isCsrfTokenValid('admin2_debtor_action', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_debtors', $request->query->all());
        }

        $debtor = $this->debtorRepository->find($id);
        if ($debtor === null) {
            $this->addFlash('error', 'Контакт не знайдено.');

            return $this->redirectToRoute('admin2_debtors', $request->query->all());
        }

        $name = $debtor->getName();
        $this->entityManager->remove($debtor);
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('Контакт «%s» видалено.', $name));

        return $this->redirectToRoute('admin2_debtors', $request->query->all());
    }

    private function createDebtor(): Debtor
    {
        $debtor = new Debtor();
        $debtor->setName('');
        $debtor->setActive(true);

        $currency = $this->entityManager->getRepository(Currency::class)->findOneBy([], ['id' => 'ASC']);
        if ($currency instanceof Currency) {
            $debtor->setCurrency($currency);
        }

        return $debtor;
    }
}
