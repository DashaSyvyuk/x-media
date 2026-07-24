<?php

namespace App\Controller\Admin2;

use App\Entity\Comment;
use App\Repository\CommentRepository;
use App\Service\Admin2\Admin2Paginator;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_USER')")]
class CommentsController extends AbstractController
{
    use Admin2BulkIdsTrait;

    public function __construct(
        private readonly CommentRepository $commentRepository,
        private readonly Admin2Paginator $admin2Paginator,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin/comments', name: 'admin2_comments', methods: ['GET'])]
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

        $query = $this->commentRepository->createAdminListQueryBuilder(
            $search,
            $status !== '' ? $status : null,
            $sort,
            $direction,
        );
        $pagination = $this->admin2Paginator->paginate($query, $page, $perPage);

        return $this->render('admin2/comments/index.html.twig', [
            'pagination'     => $pagination,
            'search'         => $search,
            'status'         => $status,
            'statusChoices'  => Comment::STATUSES,
            'sort'           => $sort,
            'direction'      => strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC',
            'perPage'        => $perPage,
            'perPageOptions' => Admin2Paginator::PER_PAGE_OPTIONS,
        ]);
    }

    #[Route('/admin/comments/{id}/delete', name: 'admin2_comments_delete', methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        if (! $this->isCsrfTokenValid('admin2_comment_action', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_comments', $request->query->all());
        }

        $comment = $this->commentRepository->find($id);
        if ($comment === null) {
            $this->addFlash('error', 'Коментар не знайдено.');

            return $this->redirectToRoute('admin2_comments', $request->query->all());
        }

        $this->entityManager->remove($comment);
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('Коментар #%d видалено.', $id));

        return $this->redirectToRoute('admin2_comments', $request->query->all());
    }

    #[Route('/admin/comments/bulk-delete', name: 'admin2_comments_bulk_delete', methods: ['POST'])]
    public function bulkDelete(Request $request): Response
    {
        if (! $this->isCsrfTokenValid('admin2_comment_bulk_delete', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_comments', $request->query->all());
        }

        $ids = $this->parseBulkIds($request);
        if ($ids === []) {
            $this->addFlash('error', 'Не вибрано жодного коментаря.');

            return $this->redirectToRoute('admin2_comments', $request->query->all());
        }

        $deleted = 0;
        foreach ($ids as $id) {
            $comment = $this->commentRepository->find($id);
            if ($comment instanceof Comment) {
                $this->entityManager->remove($comment);
                ++$deleted;
            }
        }
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('Видалено коментарів: %d.', $deleted));

        return $this->redirectToRoute('admin2_comments', $request->query->all());
    }
}
