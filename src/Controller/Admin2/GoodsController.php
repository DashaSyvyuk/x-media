<?php

namespace App\Controller\Admin2;

use App\Entity\PlanningGood;
use App\Entity\PlanningGoodBatch;
use App\Entity\Warehouse;
use App\Repository\PlanningGoodBatchRepository;
use App\Repository\PlanningGoodRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_SUPER_ADMIN')")]
class GoodsController extends AbstractController
{
    public function __construct(
        private readonly PlanningGoodBatchRepository $batchRepository,
        private readonly PlanningGoodRepository $goodRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin/goods', name: 'admin2_goods', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $view = (string) $request->query->get('view', 'grouped');
        if (! in_array($view, ['grouped', 'all'], true)) {
            $view = 'grouped';
        }

        $batches = $this->batchRepository->findAllOrdered();
        $allGoods = [];
        foreach ($batches as $batch) {
            foreach ($batch->getGoods() as $good) {
                $allGoods[] = $good;
            }
        }

        return $this->render('admin2/goods/index.html.twig', [
            'batches'     => $batches,
            'goods'       => $allGoods,
            'totals'      => self::totalsFor($allGoods),
            'view'        => $view,
            'openBatchId' => $request->query->getInt('batch'),
            'warehouses'  => $this->entityManager->getRepository(Warehouse::class)->findBy(
                ['active' => true],
                ['title' => 'ASC'],
            ),
        ]);
    }

    #[Route('/admin/goods/batches', name: 'admin2_goods_batches_create', methods: ['POST'])]
    public function createBatch(Request $request): Response
    {
        if (! $this->isCsrfTokenValid('admin2_goods', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_goods');
        }

        $dateRaw = trim((string) $request->request->get('recorded_date', ''));
        $name = trim((string) $request->request->get('name', ''));
        $date = \DateTime::createFromFormat('Y-m-d', $dateRaw) ?: null;

        if ($date === null) {
            $this->addFlash('error', 'Вкажіть дату блоку.');

            return $this->redirectToRoute('admin2_goods', ['view' => 'grouped']);
        }

        $batch = new PlanningGoodBatch();
        $batch->setRecordedDate($date);
        $batch->setName($name !== '' ? $name : null);
        $this->entityManager->persist($batch);
        $this->entityManager->flush();

        $this->addFlash('success', 'Блок створено.');

        return $this->redirectToRoute('admin2_goods', [
            'view'  => 'grouped',
            'batch' => $batch->getId(),
        ]);
    }

    #[Route(
        '/admin/goods/batches/{id}',
        name: 'admin2_goods_batches_update',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function updateBatch(Request $request, int $id): Response
    {
        if (! $this->isCsrfTokenValid('admin2_goods', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_goods');
        }

        $batch = $this->batchRepository->find($id);
        if ($batch === null) {
            $this->addFlash('error', 'Блок не знайдено.');

            return $this->redirectToRoute('admin2_goods');
        }

        $dateRaw = trim((string) $request->request->get('recorded_date', ''));
        $name = trim((string) $request->request->get('name', ''));
        $date = \DateTime::createFromFormat('Y-m-d', $dateRaw) ?: null;

        if ($date === null) {
            $this->addFlash('error', 'Вкажіть дату блоку.');

            return $this->redirectToRoute('admin2_goods', $this->redirectParams($request, $id));
        }

        $batch->setRecordedDate($date);
        $batch->setName($name !== '' ? $name : null);
        $this->entityManager->flush();

        $this->addFlash('success', 'Блок оновлено.');

        return $this->redirectToRoute('admin2_goods', $this->redirectParams($request, $id));
    }

    #[Route(
        '/admin/goods/batches/{id}/delete',
        name: 'admin2_goods_batches_delete',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function deleteBatch(Request $request, int $id): Response
    {
        if (! $this->isCsrfTokenValid('admin2_goods', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_goods');
        }

        $batch = $this->batchRepository->find($id);
        if ($batch === null) {
            $this->addFlash('error', 'Блок не знайдено.');

            return $this->redirectToRoute('admin2_goods');
        }

        $this->entityManager->remove($batch);
        $this->entityManager->flush();

        $this->addFlash('success', 'Блок і всі товари в ньому видалено.');

        return $this->redirectToRoute('admin2_goods', $this->redirectParams($request));
    }

    #[Route('/admin/goods', name: 'admin2_goods_create', methods: ['POST'])]
    public function createGood(Request $request): Response
    {
        if (! $this->isCsrfTokenValid('admin2_goods', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_goods');
        }

        $data = $this->parseGoodRequest($request, true);
        if ($data === null) {
            return $this->redirectToRoute('admin2_goods', $this->redirectParams($request));
        }

        $createCount = $data['create_count'];
        unset($data['create_count']);

        for ($i = 0; $i < $createCount; ++$i) {
            $good = new PlanningGood();
            $this->applyGoodData($good, $data);
            $this->entityManager->persist($good);
        }

        $this->entityManager->flush();

        $this->addFlash('success', $createCount === 1 ? 'Товар додано.' : sprintf('Додано %d товари.', $createCount));

        return $this->redirectToRoute('admin2_goods', $this->redirectParams($request, $data['batch']->getId()));
    }

    #[Route('/admin/goods/{id}', name: 'admin2_goods_update', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function updateGood(Request $request, int $id): Response
    {
        if (! $this->isCsrfTokenValid('admin2_goods', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_goods');
        }

        $good = $this->goodRepository->find($id);
        if ($good === null) {
            $this->addFlash('error', 'Товар не знайдено.');

            return $this->redirectToRoute('admin2_goods');
        }

        $data = $this->parseGoodRequest($request, false);
        if ($data === null) {
            return $this->redirectToRoute('admin2_goods', $this->redirectParams($request));
        }

        $this->applyGoodData($good, $data);
        $this->entityManager->flush();

        $this->addFlash('success', 'Товар оновлено.');

        return $this->redirectToRoute('admin2_goods', $this->redirectParams($request, $data['batch']->getId()));
    }

    #[Route('/admin/goods/{id}/delete', name: 'admin2_goods_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deleteGood(Request $request, int $id): Response
    {
        if (! $this->isCsrfTokenValid('admin2_goods', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectToRoute('admin2_goods');
        }

        $good = $this->goodRepository->find($id);
        if ($good === null) {
            $this->addFlash('error', 'Товар не знайдено.');

            return $this->redirectToRoute('admin2_goods');
        }

        $batchId = $good->getBatch()?->getId();
        $this->entityManager->remove($good);
        $this->entityManager->flush();

        $this->addFlash('success', 'Товар видалено.');

        return $this->redirectToRoute('admin2_goods', $this->redirectParams($request, $batchId));
    }

    /**
     * @param list<PlanningGood> $goods
     *
     * @return array{count: int, sold_count: int, purchase: float, delivery: float, sale: float, margin: float}
     */
    public static function totalsFor(array $goods): array
    {
        $purchase = 0.0;
        $delivery = 0.0;
        $sale = 0.0;
        $margin = 0.0;
        $soldCount = 0;

        foreach ($goods as $good) {
            $purchase += $good->getTotalPurchaseValue();
            $delivery += $good->getDeliveryPrice();
            $sale += $good->getTotalSaleValue();
            $itemMargin = $good->getMargin();
            if ($itemMargin !== null) {
                $margin += $itemMargin;
            }
            if ($good->isSold()) {
                ++$soldCount;
            }
        }

        return [
            'count'      => count($goods),
            'sold_count' => $soldCount,
            'purchase'   => $purchase,
            'delivery'   => $delivery,
            'sale'       => $sale,
            'margin'     => $margin,
        ];
    }

    /**
     * @return array{
     *     batch: PlanningGoodBatch,
     *     warehouse: ?Warehouse,
     *     name: string,
     *     purchase_price: float,
     *     delivery_price: float,
     *     sale_price: ?float,
     *     is_sold: bool,
     *     create_count?: int
     * }|null
     */
    private function parseGoodRequest(Request $request, bool $isCreate): ?array
    {
        $batchId = $request->request->getInt('planning_good_batch_id');
        $batch = $this->batchRepository->find($batchId);
        if ($batch === null) {
            $this->addFlash('error', 'Оберіть блок.');

            return null;
        }

        $name = trim((string) $request->request->get('name', ''));
        if ($name === '') {
            $this->addFlash('error', 'Вкажіть назву.');

            return null;
        }

        $purchaseRaw = $request->request->get('purchase_price');
        if ($purchaseRaw === null || $purchaseRaw === '' || ! is_numeric($purchaseRaw) || (float) $purchaseRaw < 0) {
            $this->addFlash('error', 'Вкажіть ціну закупки.');

            return null;
        }

        $deliveryRaw = $request->request->get('delivery_price', 0);
        $delivery = is_numeric($deliveryRaw) ? max(0.0, (float) $deliveryRaw) : 0.0;
        $isSold = $request->request->getBoolean('is_sold');
        $saleRaw = $request->request->get('sale_price');
        $salePrice = null;

        if ($isSold) {
            if ($saleRaw === null || $saleRaw === '' || ! is_numeric($saleRaw) || (float) $saleRaw < 0) {
                $this->addFlash('error', 'Вкажіть ціну продажу для проданого товару.');

                return null;
            }
            $salePrice = (float) $saleRaw;
        }

        $warehouseId = $request->request->getInt('warehouse_id');
        $warehouse = null;
        if ($warehouseId > 0) {
            $warehouse = $this->entityManager->getRepository(Warehouse::class)->find($warehouseId);
        }

        $data = [
            'batch'          => $batch,
            'warehouse'      => $warehouse,
            'name'           => $name,
            'purchase_price' => (float) $purchaseRaw,
            'delivery_price' => $delivery,
            'sale_price'     => $salePrice,
            'is_sold'        => $isSold,
        ];

        if ($isCreate) {
            $createCount = $request->request->getInt('create_count', 1);
            if ($createCount < 1 || $createCount > 999) {
                $this->addFlash('error', 'Вкажіть кількість від 1 до 999.');

                return null;
            }
            $data['create_count'] = $createCount;
        }

        return $data;
    }

    /**
     * @param array{
     *     batch: PlanningGoodBatch,
     *     warehouse: ?Warehouse,
     *     name: string,
     *     purchase_price: float,
     *     delivery_price: float,
     *     sale_price: ?float,
     *     is_sold: bool
     * } $data
     */
    private function applyGoodData(PlanningGood $good, array $data): void
    {
        $good->setBatch($data['batch']);
        $good->setWarehouse($data['warehouse']);
        $good->setName($data['name']);
        $good->setPurchasePrice($data['purchase_price']);
        $good->setDeliveryPrice($data['delivery_price']);
        $good->setIsSold($data['is_sold']);
        $good->setSalePrice($data['is_sold'] ? $data['sale_price'] : null);
    }

    /**
     * @return array{view: string, batch?: int}
     */
    private function redirectParams(Request $request, ?int $batchId = null): array
    {
        $view = (string) $request->request->get('view', $request->query->get('view', 'grouped'));
        if (! in_array($view, ['grouped', 'all'], true)) {
            $view = 'grouped';
        }

        $params = ['view' => $view];
        if ($batchId !== null && $view === 'grouped') {
            $params['batch'] = $batchId;
        }

        return $params;
    }
}
