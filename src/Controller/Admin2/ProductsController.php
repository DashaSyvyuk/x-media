<?php

namespace App\Controller\Admin2;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Service\Admin2\Admin2Paginator;
use App\Service\Admin2\ProductCloneService;
use App\Service\GenerateEkatalogXmlService;
use App\Service\GenerateHotlineXmlService;
use App\Service\GeneratePromXmlService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_USER')")]
class ProductsController extends AbstractController
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly Admin2Paginator $admin2Paginator,
        private readonly ProductCloneService $productCloneService,
        private readonly GenerateHotlineXmlService $generateHotlineXmlService,
        private readonly GeneratePromXmlService $generatePromXmlService,
        private readonly GenerateEkatalogXmlService $generateEkatalogXmlService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin/products', name: 'admin2_products', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search    = trim((string) $request->query->get('q', ''));
        $status    = $request->query->get('status');
        $sort      = (string) $request->query->get('sort', 'id');
        $direction = (string) $request->query->get('dir', 'DESC');
        $page      = $request->query->getInt('page', 1);
        $perPage = $this->admin2Paginator->normalizePerPage(
            $request->query->getInt('perPage', Admin2Paginator::DEFAULT_PER_PAGE),
        );

        $query = $this->productRepository->createAdminListQueryBuilder(
            $search,
            is_string($status) ? $status : null,
            $sort,
            $direction,
        );

        $pagination = $this->admin2Paginator->paginate($query, $page, $perPage);

        return $this->render('admin2/products/index.html.twig', [
            'pagination'      => $pagination,
            'search'          => $search,
            'status'          => is_string($status) ? $status : '',
            'sort'            => $sort,
            'direction'       => strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC',
            'perPage'         => $perPage,
            'perPageOptions'  => Admin2Paginator::PER_PAGE_OPTIONS,
            'statuses'        => [
                Product::STATUS_ACTIVE   => Product::STATUS_ACTIVE,
                Product::STATUS_BLOCKED  => Product::STATUS_BLOCKED,
            ],
        ]);
    }

    #[Route('/admin/products/generate/hotline', name: 'admin2_products_generate_hotline', methods: ['POST'])]
    public function generateHotline(Request $request): Response
    {
        return $this->handleGenerate($request, $this->generateHotlineXmlService->execute());
    }

    #[Route('/admin/products/generate/prom', name: 'admin2_products_generate_prom', methods: ['POST'])]
    public function generateProm(Request $request): Response
    {
        return $this->handleGenerate($request, $this->generatePromXmlService->execute());
    }

    #[Route('/admin/products/generate/ekatalog', name: 'admin2_products_generate_ekatalog', methods: ['POST'])]
    public function generateEkatalog(Request $request): Response
    {
        return $this->handleGenerate($request, $this->generateEkatalogXmlService->execute());
    }

    #[Route('/admin/products/{id}/clone', name: 'admin2_products_clone', methods: ['POST'])]
    public function clone(Request $request, int $id): Response
    {
        if (! $this->isCsrfTokenValid('admin2_product_action', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');
            return $this->redirectToRoute('admin2_products', $request->query->all());
        }

        $clone = $this->productCloneService->clone($id);
        if ($clone === null) {
            $this->addFlash('error', 'Товар не знайдено.');
            return $this->redirectToRoute('admin2_products', $request->query->all());
        }

        $this->addFlash('success', sprintf('Товар #%d скопійовано як #%d.', $id, $clone->getId()));

        return $this->redirectToRoute('admin2_products_edit', ['id' => $clone->getId()]);
    }

    #[Route('/admin/products/{id}/delete', name: 'admin2_products_delete', methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        if (! $this->isCsrfTokenValid('admin2_product_action', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');
            return $this->redirectToRoute('admin2_products', $request->query->all());
        }

        $product = $this->productRepository->find($id);
        if ($product === null) {
            $this->addFlash('error', 'Товар не знайдено.');
            return $this->redirectToRoute('admin2_products', $request->query->all());
        }

        $this->entityManager->remove($product);
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('Товар #%d видалено.', $id));

        return $this->redirectToRoute('admin2_products', $request->query->all());
    }

    private function handleGenerate(Request $request, ?string $url): Response
    {
        if (! $this->isCsrfTokenValid('admin2_products_generate', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_products', $this->extractListParams($request));
        }

        if ($url === null) {
            $this->addFlash('error', 'Не вдалося згенерувати XML. Перевірте логи.');
        } else {
            $this->addFlash(
                'success',
                sprintf(
                    'XML згенеровано: <a href="%s" target="_blank" class="alert-link">відкрити файл</a>',
                    htmlspecialchars($url),
                ),
            );
        }

        return $this->redirectToRoute('admin2_products', $this->extractListParams($request));
    }

    /** @return array<string, mixed> */
    private function extractListParams(Request $request): array
    {
        $params = [];
        foreach (['q', 'status', 'sort', 'dir', 'perPage', 'page'] as $key) {
            $value = $request->request->has($key)
                ? $request->request->get($key)
                : $request->query->get($key);

            if ($value !== null && $value !== '') {
                $params[$key] = $value;
            }
        }

        return $params;
    }
}
