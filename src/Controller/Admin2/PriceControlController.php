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
        $perPage    = $this->admin2Paginator->normalizePerPage($this->toNullableInt($request->query->get('perPage')) ?? 50);

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

        $product = $this->productRepository->find($id);
        if ($product === null) {
            return new JsonResponse(['ok' => false, 'error' => 'Product not found'], 404);
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
        if ($price < 0 || ($oldPrice !== null && $oldPrice < 0) || ($rzPrice !== null && $rzPrice < 0) || ($rzOld !== null && $rzOld < 0)) {
            return new JsonResponse(['ok' => false, 'error' => 'Price must be >= 0'], 400);
        }

        $product->setStatus($status);
        $product->setPrice($price);
        $product->setCrossedOutPrice($oldPrice);

        $rozetka = $this->rozetkaProductRepository->findOneBy(['product' => $product]);
        if ($rozetka !== null) {
            $rozetka->setPrice($rzPrice);
            $rozetka->setCrossedOutPrice($rzOld);
        }

        $this->entityManager->flush();

        return new JsonResponse(['ok' => true]);
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
}
