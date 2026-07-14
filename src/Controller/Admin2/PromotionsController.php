<?php

namespace App\Controller\Admin2;

use App\Repository\PromotionRepository;
use App\Service\Admin2\Admin2Paginator;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_USER')")]
class PromotionsController extends AbstractController
{
    public function __construct(
        private readonly PromotionRepository $promotionRepository,
        private readonly Admin2Paginator $admin2Paginator,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin2/promotions', name: 'admin2_promotions', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search    = trim((string) $request->query->get('q', ''));
        $sort      = (string) $request->query->get('sort', 'id');
        $direction = (string) $request->query->get('dir', 'DESC');
        $page      = $request->query->getInt('page', 1);
        $perPage = $this->admin2Paginator->normalizePerPage(
            $request->query->getInt('perPage', Admin2Paginator::DEFAULT_PER_PAGE),
        );

        $query = $this->promotionRepository->createAdminListQueryBuilder($search, $sort, $direction);
        $pagination = $this->admin2Paginator->paginate($query, $page, $perPage);

        $promotionIds = [];
        foreach ($pagination as $promotion) {
            $promotionIds[] = $promotion->getId();
        }

        return $this->render('admin2/promotions/index.html.twig', [
            'pagination'     => $pagination,
            'productCounts'  => $this->promotionRepository->countProductsByPromotionIds($promotionIds),
            'search'         => $search,
            'sort'           => $sort,
            'direction'      => strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC',
            'perPage'        => $perPage,
            'perPageOptions' => Admin2Paginator::PER_PAGE_OPTIONS,
        ]);
    }

    #[Route('/admin2/promotions/{id}/delete', name: 'admin2_promotions_delete', methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        if (! $this->isCsrfTokenValid('admin2_promotion_action', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_promotions', $request->query->all());
        }

        $promotion = $this->promotionRepository->find($id);
        if ($promotion === null) {
            $this->addFlash('error', 'Акцію не знайдено.');

            return $this->redirectToRoute('admin2_promotions', $request->query->all());
        }

        $title = $promotion->getTitle();
        $this->entityManager->remove($promotion);
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('Акцію «%s» видалено.', $title));

        return $this->redirectToRoute('admin2_promotions', $request->query->all());
    }
}
