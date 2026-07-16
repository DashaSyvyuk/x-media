<?php

namespace App\Controller\Admin2;

use App\Entity\Filter;
use App\Repository\CategoryRepository;
use App\Repository\FilterRepository;
use App\Service\Admin2\Admin2Paginator;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_USER')")]
class FiltersController extends AbstractController
{
    public function __construct(
        private readonly FilterRepository $filterRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly Admin2Paginator $admin2Paginator,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin/filters', name: 'admin2_filters', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search     = trim((string) $request->query->get('q', ''));
        $categoryId = $request->query->getInt('category', 0) ?: null;
        $sort       = (string) $request->query->get('sort', 'title');
        $direction  = (string) $request->query->get('dir', 'ASC');
        $page       = $request->query->getInt('page', 1);
        $perPage = $this->admin2Paginator->normalizePerPage(
            $request->query->getInt('perPage', Admin2Paginator::DEFAULT_PER_PAGE),
        );

        $query = $this->filterRepository->createAdminListQueryBuilder(
            $search,
            $categoryId,
            $sort,
            $direction,
        );

        $pagination = $this->admin2Paginator->paginate($query, $page, $perPage);

        return $this->render('admin2/filters/index.html.twig', [
            'pagination'     => $pagination,
            'search'         => $search,
            'categoryId'     => $categoryId ?? 0,
            'categories'     => $this->categoryRepository->findBy([], ['title' => 'ASC']),
            'sort'           => $sort,
            'direction'      => strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC',
            'perPage'        => $perPage,
            'perPageOptions' => Admin2Paginator::PER_PAGE_OPTIONS,
        ]);
    }

    #[Route('/admin/filters/{id}/delete', name: 'admin2_filters_delete', methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        if (! $this->isCsrfTokenValid('admin2_filter_action', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');
            return $this->redirectToRoute('admin2_filters', $request->query->all());
        }

        $filter = $this->filterRepository->find($id);
        if ($filter === null) {
            $this->addFlash('error', 'Фільтр не знайдено.');
            return $this->redirectToRoute('admin2_filters', $request->query->all());
        }

        $this->entityManager->remove($filter);
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('Фільтр «%s» видалено.', $filter->getTitle()));

        return $this->redirectToRoute('admin2_filters', $request->query->all());
    }
}
