<?php

namespace App\Controller\Admin2;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Service\Admin2\Admin2Paginator;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_USER')")]
class CategoriesController extends AbstractController
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly Admin2Paginator $admin2Paginator,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin2/categories', name: 'admin2_categories', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search    = trim((string) $request->query->get('q', ''));
        $status    = $request->query->get('status');
        $sort      = (string) $request->query->get('sort', 'title');
        $direction = (string) $request->query->get('dir', 'ASC');
        $page      = $request->query->getInt('page', 1);
        $perPage   = $this->admin2Paginator->normalizePerPage($request->query->getInt('perPage', Admin2Paginator::DEFAULT_PER_PAGE));

        $query = $this->categoryRepository->createAdminListQueryBuilder(
            $search,
            is_string($status) && $status !== '' ? $status : null,
            $sort,
            $direction,
        );

        $pagination = $this->admin2Paginator->paginate($query, $page, $perPage);

        return $this->render('admin2/categories/index.html.twig', [
            'pagination'     => $pagination,
            'search'         => $search,
            'status'         => is_string($status) ? $status : '',
            'sort'           => $sort,
            'direction'      => strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC',
            'perPage'        => $perPage,
            'perPageOptions' => Admin2Paginator::PER_PAGE_OPTIONS,
            'statuses'       => Category::STATUSES,
        ]);
    }

    #[Route('/admin2/categories/{id}/delete', name: 'admin2_categories_delete', methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        if (! $this->isCsrfTokenValid('admin2_category_action', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');
            return $this->redirectToRoute('admin2_categories', $request->query->all());
        }

        $category = $this->categoryRepository->find($id);
        if ($category === null) {
            $this->addFlash('error', 'Категорію не знайдено.');
            return $this->redirectToRoute('admin2_categories', $request->query->all());
        }

        $title = $category->getTitle();
        $this->entityManager->remove($category);
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('Категорію «%s» видалено.', $title));

        return $this->redirectToRoute('admin2_categories', $request->query->all());
    }
}
