<?php

namespace App\Controller\Admin2;

use App\Entity\Feed;
use App\Repository\FeedRepository;
use App\Service\Admin2\Admin2Paginator;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_ADMIN')")]
class FeedsController extends AbstractController
{
    public function __construct(
        private readonly FeedRepository $feedRepository,
        private readonly Admin2Paginator $admin2Paginator,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin/feeds', name: 'admin2_feeds', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search    = trim((string) $request->query->get('q', ''));
        $sort      = (string) $request->query->get('sort', 'id');
        $direction = (string) $request->query->get('dir', 'DESC');
        $page      = $request->query->getInt('page', 1);
        $perPage = $this->admin2Paginator->normalizePerPage(
            $request->query->getInt('perPage', Admin2Paginator::DEFAULT_PER_PAGE),
        );

        $qb = $this->feedRepository->createQueryBuilder('f');

        if ($search !== '') {
            $qb->andWhere('LOWER(f.type) LIKE :search')
                ->setParameter('search', '%' . mb_strtolower($search) . '%');
        }

        $allowedSorts = ['id', 'type', 'updatedAt'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'id';
        }

        $qb->orderBy('f.' . $sort, strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC');

        return $this->render('admin2/feeds/index.html.twig', [
            'pagination'     => $this->admin2Paginator->paginate($qb, $page, $perPage),
            'search'         => $search,
            'sort'           => $sort,
            'direction'      => strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC',
            'perPage'        => $perPage,
            'perPageOptions' => Admin2Paginator::PER_PAGE_OPTIONS,
            'typeLabels'     => array_flip(Feed::TYPES),
        ]);
    }

    #[Route('/admin/feeds/{id}/delete', name: 'admin2_feeds_delete', methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        if (! $this->isCsrfTokenValid('admin2_feed_action', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_feeds', $request->query->all());
        }

        $feed = $this->feedRepository->find($id);
        if ($feed instanceof Feed) {
            $this->entityManager->remove($feed);
            $this->entityManager->flush();
            $this->addFlash('success', sprintf('Feed «%s» видалено.', $feed->getType()));
        }

        return $this->redirectToRoute('admin2_feeds', $request->query->all());
    }
}
