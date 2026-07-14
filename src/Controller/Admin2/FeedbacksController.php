<?php

namespace App\Controller\Admin2;

use App\Entity\Feedback;
use App\Repository\FeedbackRepository;
use App\Service\Admin2\Admin2Paginator;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_USER')")]
class FeedbacksController extends AbstractController
{
    public function __construct(
        private readonly FeedbackRepository $feedbackRepository,
        private readonly Admin2Paginator $admin2Paginator,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin2/feedbacks', name: 'admin2_feedbacks', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search    = trim((string) $request->query->get('q', ''));
        $status    = (string) $request->query->get('status', '');
        $sort      = (string) $request->query->get('sort', 'id');
        $direction = (string) $request->query->get('dir', 'DESC');
        $page      = $request->query->getInt('page', 1);
        $perPage   = $this->admin2Paginator->normalizePerPage($request->query->getInt('perPage', Admin2Paginator::DEFAULT_PER_PAGE));

        $query = $this->feedbackRepository->createAdminListQueryBuilder(
            $search,
            $status !== '' ? $status : null,
            $sort,
            $direction,
        );
        $pagination = $this->admin2Paginator->paginate($query, $page, $perPage);

        return $this->render('admin2/feedbacks/index.html.twig', [
            'pagination'     => $pagination,
            'search'         => $search,
            'status'         => $status,
            'statusChoices'  => Feedback::STATUSES,
            'sort'           => $sort,
            'direction'      => strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC',
            'perPage'        => $perPage,
            'perPageOptions' => Admin2Paginator::PER_PAGE_OPTIONS,
        ]);
    }

    #[Route('/admin2/feedbacks/{id}/delete', name: 'admin2_feedbacks_delete', methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        if (! $this->isCsrfTokenValid('admin2_feedback_action', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_feedbacks', $request->query->all());
        }

        $feedback = $this->feedbackRepository->find($id);
        if ($feedback === null) {
            $this->addFlash('error', 'Відгук не знайдено.');

            return $this->redirectToRoute('admin2_feedbacks', $request->query->all());
        }

        $this->entityManager->remove($feedback);
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('Відгук #%d видалено.', $id));

        return $this->redirectToRoute('admin2_feedbacks', $request->query->all());
    }
}
