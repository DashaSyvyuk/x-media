<?php

namespace App\Controller\Admin2;

use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\RozetkaProductRepository;
use App\Service\Admin2\Admin2Paginator;
use App\Service\Admin2\PlnExchangeRateProvider;
use App\Service\Admin2\XKomPriceFetcher;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Security("is_granted('ROLE_USER')")]
class PriceControlController extends AbstractController
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly RozetkaProductRepository $rozetkaProductRepository,
        private readonly Admin2Paginator $admin2Paginator,
        private readonly XKomPriceFetcher $xKomPriceFetcher,
        private readonly PlnExchangeRateProvider $plnExchangeRateProvider,
        private readonly EntityManagerInterface $entityManager,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
    ) {
    }

    #[Route('/admin/price-control', name: 'admin2_price_control', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search     = trim((string) $request->query->get('q', ''));
        $status     = $request->query->get('status');
        $categoryId = $this->toNullableInt($request->query->get('category'));
        $sort       = (string) $request->query->get('sort', 'id');
        $direction  = (string) $request->query->get('dir', 'DESC');
        $page       = max(1, $this->toNullableInt($request->query->get('page')) ?? 1);
        $perPage = $this->admin2Paginator->normalizePerPage(
            $this->toNullableInt($request->query->get('perPage')) ?? 50,
        );

        $query = $this->productRepository->createPriceControlQueryBuilder(
            $search,
            is_string($status) ? $status : null,
            $categoryId,
            $sort,
            $direction,
        );

        $pagination = $this->admin2Paginator->paginate($query, $page, $perPage);
        $products = iterator_to_array($pagination);
        $plnRate = $this->plnExchangeRateProvider->getRateUahPerPln();

        return $this->render('admin2/price_control/index.html.twig', [
            'pagination'     => $pagination,
            'xkomInfo'       => $this->xKomPriceFetcher->fetchForProducts($products),
            'plnRate'        => $plnRate,
            'categories'     => $this->categoryRepository->findBy([], ['title' => 'ASC']),
            'search'         => $search,
            'status'         => is_string($status) ? $status : '',
            'categoryId'     => $categoryId ?? 0,
            'sort'           => $sort,
            'direction'      => strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC',
            'perPage'        => $perPage,
            'perPageOptions' => Admin2Paginator::PER_PAGE_OPTIONS,
            'statuses'       => [
                Product::STATUS_ACTIVE  => Product::STATUS_ACTIVE,
                Product::STATUS_BLOCKED => Product::STATUS_BLOCKED,
            ],
            'updateToken'    => $this->csrfTokenManager->getToken('admin2_price_control_update')->getValue(),
        ]);
    }

    #[Route('/admin/price-control/update', name: 'admin2_price_control_update', methods: ['POST'])]
    public function update(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (! is_array($data)) {
            return new JsonResponse(['ok' => false, 'error' => 'Invalid JSON'], 400);
        }

        $id    = (int) ($data['id'] ?? 0);
        $token = (string) ($data['_token'] ?? '');

        if ($id <= 0) {
            return new JsonResponse(['ok' => false, 'error' => 'Invalid id'], 400);
        }

        if (! $this->csrfTokenManager->isTokenValid(new CsrfToken('admin2_price_control_update', $token))) {
            return new JsonResponse(['ok' => false, 'error' => 'CSRF invalid'], 403);
        }

        $status = (string) ($data['status'] ?? '');
        if (! in_array($status, [Product::STATUS_ACTIVE, Product::STATUS_BLOCKED], true)) {
            return new JsonResponse(['ok' => false, 'error' => 'Invalid status'], 400);
        }

        $price   = $this->toNullableInt($data['price'] ?? null);
        $oldPrice = $this->toNullableInt($data['crossed_out_price'] ?? null);
        $rzPrice = $this->toNullableInt($data['rozetka_price'] ?? null);
        $rzOld   = $this->toNullableInt($data['rozetka_crossed_out_price'] ?? null);

        if ($price === null) {
            return new JsonResponse(['ok' => false, 'error' => 'Price is required'], 400);
        }
        if (
            $price < 0
            || ($oldPrice !== null && $oldPrice < 0)
            || ($rzPrice !== null && $rzPrice < 0)
            || ($rzOld !== null && $rzOld < 0)
        ) {
            return new JsonResponse(['ok' => false, 'error' => 'Price must be >= 0'], 400);
        }

        $product = $this->productRepository->createQueryBuilder('p')
            ->leftJoin('p.rozetka', 'rozetka')->addSelect('rozetka')
            ->where('p.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();

        if (! $product instanceof Product) {
            return new JsonResponse(['ok' => false, 'error' => 'Product not found'], 404);
        }

        $product->setStatus($status);
        $product->setPrice($price);
        $product->setCrossedOutPrice($oldPrice);

        $rozetka = $product->getRozetka()
            ?? $this->rozetkaProductRepository->findByAttachedProductId($product->getId());

        if ($rozetka !== null) {
            if ($product->getRozetka() === null) {
                $product->setRozetka($rozetka);
            }
            $rozetka->setPrice($rzPrice);
            $rozetka->setCrossedOutPrice($rzOld);
        }

        $this->entityManager->flush();

        // Resolve again: ProductSubscriber may create Rozetka on flush.
        $rozetka = $product->getRozetka()
            ?? $this->rozetkaProductRepository->findByAttachedProductId($product->getId());

        if ($rozetka !== null) {
            $rozetka->setPrice($rzPrice);
            $rozetka->setCrossedOutPrice($rzOld);
            $product->setCrossedOutPrice($oldPrice);
            $this->entityManager->flush();
        }

        return new JsonResponse([
            'ok'                        => true,
            'price'                     => $product->getPrice(),
            'crossed_out_price'         => $product->getCrossedOutPrice(),
            'rozetka_price'             => $rozetka?->getPrice(),
            'rozetka_crossed_out_price' => $rozetka?->getCrossedOutPrice(),
        ]);
    }

    #[Route('/admin/price-control/bulk-price', name: 'admin2_price_control_bulk_price', methods: ['POST'])]
    public function bulkPrice(Request $request): Response
    {
        if (! $this->isCsrfTokenValid('admin2_price_control_bulk_price', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_price_control');
        }

        $categoryId = (int) $request->request->get('category_id', 0);
        $delta = (int) $request->request->get('delta', 0);

        if ($categoryId <= 0 || $delta === 0) {
            $this->addFlash('error', 'Оберіть категорію та вкажіть зміну ціни (не 0).');

            return $this->redirectToRoute('admin2_price_control');
        }

        $category = $this->categoryRepository->find($categoryId);
        if ($category === null) {
            $this->addFlash('error', 'Категорію не знайдено.');

            return $this->redirectToRoute('admin2_price_control');
        }

        $updatedSite = $this->productRepository->adjustPriceForCategory($categoryId, $delta);
        $updatedRozetka = $this->rozetkaProductRepository->adjustPriceForCategory($categoryId, $delta);
        $sign = $delta > 0 ? '+' : '';
        $this->addFlash(
            'success',
            sprintf(
                'Ціни змінено на %s%d ₴ у категорії «%s»: сайт — %d, Rozetka — %d.',
                $sign,
                $delta,
                $category->getTitle(),
                $updatedSite,
                $updatedRozetka,
            ),
        );

        return $this->redirectToRoute('admin2_price_control');
    }

    #[Route('/admin/price-control/bulk-price-search', name: 'admin2_price_control_bulk_price_search', methods: ['POST'])]
    public function bulkPriceSearch(Request $request): Response
    {
        $listParams = $this->extractListParams($request);

        if (! $this->isCsrfTokenValid('admin2_price_control_bulk_price_search', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_price_control', $listParams);
        }

        $search = trim((string) ($listParams['q'] ?? ''));
        $status = isset($listParams['status']) && is_string($listParams['status']) ? $listParams['status'] : null;
        $categoryId = $this->toNullableInt($listParams['category'] ?? null);
        $delta = (int) $request->request->get('delta', 0);

        if (! $this->productRepository->hasPriceControlFilters($search, $status, $categoryId)) {
            $this->addFlash('error', 'Спочатку задайте пошук, статус або категорію у фільтрах.');

            return $this->redirectToRoute('admin2_price_control', $listParams);
        }

        if ($delta === 0) {
            $this->addFlash('error', 'Вкажіть зміну ціни (не 0).');

            return $this->redirectToRoute('admin2_price_control', $listParams);
        }

        $updatedSite = $this->productRepository->adjustPriceForPriceControlFilters(
            $search,
            $status,
            $categoryId,
            $delta,
        );
        $updatedRozetka = $this->rozetkaProductRepository->adjustPriceForPriceControlFilters(
            $search,
            $status,
            $categoryId,
            $delta,
        );
        $sign = $delta > 0 ? '+' : '';
        $this->addFlash(
            'success',
            sprintf(
                'Ціни змінено на %s%d ₴ для поточного результату пошуку: сайт — %d, Rozetka — %d.',
                $sign,
                $delta,
                $updatedSite,
                $updatedRozetka,
            ),
        );

        return $this->redirectToRoute('admin2_price_control', $listParams);
    }

    #[Route('/admin/price-control/bulk-status-search', name: 'admin2_price_control_bulk_status_search', methods: ['POST'])]
    public function bulkStatusSearch(Request $request): Response
    {
        $listParams = $this->extractListParams($request);

        if (! $this->isCsrfTokenValid('admin2_price_control_bulk_status_search', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_price_control', $listParams);
        }

        $search = trim((string) ($listParams['q'] ?? ''));
        $statusFilter = isset($listParams['status']) && is_string($listParams['status']) ? $listParams['status'] : null;
        $categoryId = $this->toNullableInt($listParams['category'] ?? null);
        $status = (string) $request->request->get('status', '');

        if (! $this->productRepository->hasPriceControlFilters($search, $statusFilter, $categoryId)) {
            $this->addFlash('error', 'Спочатку задайте пошук, статус або категорію у фільтрах.');

            return $this->redirectToRoute('admin2_price_control', $listParams);
        }

        if (! in_array($status, [Product::STATUS_ACTIVE, Product::STATUS_BLOCKED], true)) {
            $this->addFlash('error', 'Некоректний статус.');

            return $this->redirectToRoute('admin2_price_control', $listParams);
        }

        $updated = $this->productRepository->setStatusForPriceControlFilters(
            $search,
            $statusFilter,
            $categoryId,
            $status,
        );
        $this->addFlash(
            'success',
            sprintf('Статус «%s» застосовано до %d товар(ів) з поточного результату пошуку.', $status, $updated),
        );

        return $this->redirectToRoute('admin2_price_control', $listParams);
    }

    #[Route('/admin/price-control/xkom-url', name: 'admin2_price_control_xkom_url', methods: ['POST'])]
    public function updateXkomUrl(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (! is_array($data)) {
            return new JsonResponse(['ok' => false, 'error' => 'Invalid JSON'], 400);
        }

        $id    = (int) ($data['id'] ?? 0);
        $token = (string) ($data['_token'] ?? '');
        $url   = trim((string) ($data['xkom_url'] ?? ''));

        if ($id <= 0) {
            return new JsonResponse(['ok' => false, 'error' => 'Invalid id'], 400);
        }

        if (! $this->csrfTokenManager->isTokenValid(new CsrfToken('admin2_price_control_update', $token))) {
            return new JsonResponse(['ok' => false, 'error' => 'CSRF invalid'], 403);
        }

        if ($url === '' || ! $this->xKomPriceFetcher->isXKomUrl($url)) {
            return new JsonResponse(['ok' => false, 'error' => 'Invalid x-kom url'], 400);
        }

        $product = $this->productRepository->find($id);
        if ($product === null) {
            return new JsonResponse(['ok' => false, 'error' => 'Product not found'], 404);
        }

        $product->setXkomUrl($url);
        $this->entityManager->flush();

        return new JsonResponse(['ok' => true]);
    }

    private function toNullableInt(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return null;
            }
        }

        if (! is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    /**
     * @return array{q?: string, status?: string, category?: int, sort?: string, dir?: string, perPage?: int, page?: int}
     */
    private function extractListParams(Request $request): array
    {
        $source = $request->isMethod('POST') && $request->query->count() === 0
            ? $request->request
            : $request->query;

        $params = [];

        $search = trim((string) $source->get('q', $request->query->get('q', '')));
        if ($search !== '') {
            $params['q'] = $search;
        }

        $status = $source->get('status', $request->query->get('status'));
        if (is_string($status) && $status !== '') {
            $params['status'] = $status;
        }

        $category = $this->toNullableInt($source->get('category', $request->query->get('category')));
        if ($category !== null && $category > 0) {
            $params['category'] = $category;
        }

        $sort = (string) $source->get('sort', $request->query->get('sort', 'id'));
        if ($sort !== '') {
            $params['sort'] = $sort;
        }

        $direction = strtoupper((string) $source->get('dir', $request->query->get('dir', 'DESC')));
        $params['dir'] = $direction === 'ASC' ? 'ASC' : 'DESC';

        $perPage = $this->toNullableInt($source->get('perPage', $request->query->get('perPage')));
        if ($perPage !== null) {
            $params['perPage'] = $this->admin2Paginator->normalizePerPage($perPage);
        }

        $page = $this->toNullableInt($source->get('page', $request->query->get('page')));
        if ($page !== null && $page > 1) {
            $params['page'] = $page;
        }

        return $params;
    }
}
