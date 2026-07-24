<?php

namespace App\Controller\Admin2;

use App\Entity\RozetkaProduct;
use App\Repository\CategoryRepository;
use App\Repository\RozetkaProductRepository;
use App\Service\Admin2\Admin2Paginator;
use App\Service\GenerateRozetkaXmlService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_ADMIN') or is_granted('ROLE_USER')")]
class RozetkaProductsController extends AbstractController
{
    use Admin2BulkIdsTrait;

    public function __construct(
        private readonly RozetkaProductRepository $rozetkaProductRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly Admin2Paginator $admin2Paginator,
        private readonly GenerateRozetkaXmlService $generateRozetkaXmlService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin/rozetka', name: 'admin2_rozetka', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search    = trim((string) $request->query->get('q', ''));
        $ready     = $request->query->get('ready');
        $feed      = $request->query->get('feed');
        $sort      = (string) $request->query->get('sort', 'productId');
        $direction = (string) $request->query->get('dir', 'DESC');
        $page      = $request->query->getInt('page', 1);
        $perPage = $this->admin2Paginator->normalizePerPage(
            $request->query->getInt('perPage', Admin2Paginator::DEFAULT_PER_PAGE),
        );

        $query = $this->rozetkaProductRepository->createAdminListQueryBuilder(
            $search,
            is_string($ready) ? $ready : null,
            is_string($feed) ? $feed : null,
            $sort,
            $direction,
        );

        return $this->render('admin2/rozetka/index.html.twig', [
            'pagination'     => $this->admin2Paginator->paginate($query, $page, $perPage),
            'summary'        => $this->rozetkaProductRepository->getSummaryCounts(),
            'categories'     => $this->categoryRepository->findBy([], ['title' => 'ASC']),
            'search'         => $search,
            'ready'          => is_string($ready) ? $ready : '',
            'feed'           => is_string($feed) ? $feed : '',
            'sort'           => $sort,
            'direction'      => strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC',
            'perPage'        => $perPage,
            'perPageOptions' => Admin2Paginator::PER_PAGE_OPTIONS,
        ]);
    }

    #[Route('/admin/rozetka/bulk-price', name: 'admin2_rozetka_bulk_price', methods: ['POST'])]
    public function bulkPrice(Request $request): Response
    {
        if (! $this->isCsrfTokenValid('admin2_rozetka_bulk_price', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_rozetka');
        }

        $categoryId = (int) $request->request->get('category_id', 0);
        $delta = (int) $request->request->get('delta', 0);

        if ($categoryId <= 0 || $delta === 0) {
            $this->addFlash('error', 'Оберіть категорію та вкажіть зміну ціни (не 0).');

            return $this->redirectToRoute('admin2_rozetka');
        }

        $category = $this->categoryRepository->find($categoryId);
        if ($category === null) {
            $this->addFlash('error', 'Категорію не знайдено.');

            return $this->redirectToRoute('admin2_rozetka');
        }

        $updated = $this->rozetkaProductRepository->adjustPriceForCategory($categoryId, $delta);
        $sign = $delta > 0 ? '+' : '';
        $this->addFlash(
            'success',
            sprintf(
                'Ціну Rozetka змінено на %s%d ₴ для %d товар(ів) у категорії «%s».',
                $sign,
                $delta,
                $updated,
                $category->getTitle(),
            ),
        );

        return $this->redirectToRoute('admin2_rozetka');
    }

    #[Route('/admin/rozetka/bulk-price-selected', name: 'admin2_rozetka_bulk_price_selected', methods: ['POST'])]
    public function bulkPriceSelected(Request $request): Response
    {
        $token = (string) $request->request->get('_token');
        if (! $this->isCsrfTokenValid('admin2_rozetka_bulk_price_selected', $token)) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_rozetka', $request->query->all());
        }

        $ids = $this->parseBulkIds($request);
        $delta = (int) $request->request->get('delta', 0);

        if ($ids === [] || $delta === 0) {
            $this->addFlash('error', 'Виберіть товари та вкажіть зміну ціни (не 0).');

            return $this->redirectToRoute('admin2_rozetka', $request->query->all());
        }

        $updated = $this->rozetkaProductRepository->adjustPriceForIds($ids, $delta);
        $sign = $delta > 0 ? '+' : '';
        $this->addFlash(
            'success',
            sprintf('Ціну Rozetka змінено на %s%d ₴ для %d вибраних товар(ів).', $sign, $delta, $updated),
        );

        return $this->redirectToRoute('admin2_rozetka', $request->query->all());
    }

    #[Route('/admin/rozetka/bulk-ready', name: 'admin2_rozetka_bulk_ready', methods: ['POST'])]
    public function bulkReady(Request $request): Response
    {
        if (! $this->isCsrfTokenValid('admin2_rozetka_bulk_ready', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_rozetka', $request->query->all());
        }

        $ids = $this->parseBulkIds($request);
        $readyRaw = $request->request->get('ready');
        $ready = filter_var($readyRaw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($ids === [] || $ready === null) {
            $this->addFlash('error', 'Виберіть товари та дію (активні / заблоковані).');

            return $this->redirectToRoute('admin2_rozetka', $request->query->all());
        }

        $updated = $this->rozetkaProductRepository->setReadyForIds($ids, $ready);
        $this->addFlash(
            'success',
            sprintf(
                '%s для %d товар(ів) Rozetka.',
                $ready ? 'Увімкнено «Готовий»' : 'Вимкнено «Готовий» (A/P також скинуто)',
                $updated,
            ),
        );

        return $this->redirectToRoute('admin2_rozetka', $request->query->all());
    }

    #[Route('/admin/rozetka/generate/a', name: 'admin2_rozetka_generate_a', methods: ['POST'])]
    public function generateA(Request $request): Response
    {
        return $this->handleGenerate($request, 'active_for_a');
    }

    #[Route('/admin/rozetka/generate/p', name: 'admin2_rozetka_generate_p', methods: ['POST'])]
    public function generateP(Request $request): Response
    {
        return $this->handleGenerate($request, 'active_for_p');
    }

    #[Route('/admin/rozetka/{id}/toggle', name: 'admin2_rozetka_toggle', methods: ['POST'])]
    public function toggle(Request $request, int $id): JsonResponse
    {
        if (! $this->isCsrfTokenValid('admin2_rozetka_toggle', (string) $request->request->get('_token'))) {
            return new JsonResponse(['ok' => false, 'error' => 'CSRF invalid'], 403);
        }

        $field = (string) $request->request->get('field', '');
        $value = filter_var($request->request->get('value'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if (! in_array($field, ['ready', 'activeForA', 'activeForP'], true) || $value === null) {
            return new JsonResponse(['ok' => false, 'error' => 'Invalid payload'], 400);
        }

        $rozetkaProduct = $this->rozetkaProductRepository->find($id);
        if (! $rozetkaProduct instanceof RozetkaProduct) {
            return new JsonResponse(['ok' => false, 'error' => 'Not found'], 404);
        }

        if (in_array($field, ['activeForA', 'activeForP'], true) && ! $rozetkaProduct->getReady()) {
            return new JsonResponse(['ok' => false, 'error' => 'Product is not ready'], 422);
        }

        match ($field) {
            'ready'      => $rozetkaProduct->setReady($value),
            'activeForA' => $rozetkaProduct->setActiveForA($value),
            'activeForP' => $rozetkaProduct->setActiveForP($value),
        };

        $this->entityManager->flush();

        return new JsonResponse([
            'ok'    => true,
            'value' => $value,
        ]);
    }

    private function handleGenerate(Request $request, string $mode): Response
    {
        if (! $this->isCsrfTokenValid('admin2_rozetka_generate', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_rozetka', $this->extractListParams($request));
        }

        $url = $this->generateRozetkaXmlService->execute($mode);

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

        return $this->redirectToRoute('admin2_rozetka', $this->extractListParams($request));
    }

    /** @return array<string, mixed> */
    private function extractListParams(Request $request): array
    {
        $params = [];
        foreach (['q', 'ready', 'feed', 'sort', 'dir', 'perPage', 'page'] as $key) {
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
