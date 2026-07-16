<?php

namespace App\Controller\Admin2;

use App\Entity\VendorOrder;
use App\Entity\VendorOrderItem;
use App\Form\Admin2\VendorOrderType;
use App\Repository\SupplierRepository;
use App\Repository\VendorOrderRepository;
use App\Service\Admin2\Admin2Paginator;
use App\Service\Admin2\OrderClipboardFormatter;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_SUPER_ADMIN')")]
class VendorOrdersController extends AbstractController
{
    public function __construct(
        private readonly VendorOrderRepository $vendorOrderRepository,
        private readonly SupplierRepository $supplierRepository,
        private readonly Admin2Paginator $admin2Paginator,
        private readonly OrderClipboardFormatter $clipboardFormatter,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin/vendor-orders', name: 'admin2_vendor_orders', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search     = trim((string) $request->query->get('q', ''));
        $status     = (string) $request->query->get('status', '');
        $supplierId = $request->query->getInt('supplier', 0) ?: null;
        $sort       = (string) $request->query->get('sort', 'id');
        $direction  = (string) $request->query->get('dir', 'DESC');
        $page       = $request->query->getInt('page', 1);
        $perPage = $this->admin2Paginator->normalizePerPage(
            $request->query->getInt('perPage', Admin2Paginator::DEFAULT_PER_PAGE),
        );

        $query = $this->vendorOrderRepository->createAdminListQueryBuilder(
            $search,
            $status,
            $supplierId,
            $sort,
            $direction,
        );

        return $this->render('admin2/vendor_orders/index.html.twig', [
            'pagination'     => $this->admin2Paginator->paginate($query, $page, $perPage),
            'suppliers'      => $this->supplierRepository->findBy(['active' => true], ['title' => 'ASC']),
            'statusChoices'  => VendorOrder::STATUSES,
            'search'         => $search,
            'status'         => $status,
            'supplierId'     => $supplierId ?? 0,
            'sort'           => $sort,
            'direction'      => strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC',
            'perPage'        => $perPage,
            'perPageOptions' => Admin2Paginator::PER_PAGE_OPTIONS,
        ]);
    }

    #[Route('/admin/vendor-orders/new', name: 'admin2_vendor_orders_new', methods: ['GET', 'POST'])]
    public function createNew(Request $request): Response
    {
        $order = new VendorOrder();
        $order->setStatus(VendorOrder::STATUS_NEW);
        $order->addItem(new VendorOrderItem());

        return $this->handleForm($request, $order, true);
    }

    #[Route('/admin/vendor-orders/{id}/edit', name: 'admin2_vendor_orders_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $order = $this->vendorOrderRepository->find($id);
        if (! $order instanceof VendorOrder) {
            throw $this->createNotFoundException('Замовлення постачальника не знайдено.');
        }

        if ($order->getItems()->isEmpty() && trim($order->getProductTitle()) !== '') {
            $legacyItem = new VendorOrderItem();
            $legacyItem->setTitle($order->getProductTitle());
            $legacyItem->setPrice($order->getPrice());
            $order->addItem($legacyItem);
        }

        return $this->handleForm($request, $order, false);
    }

    #[Route('/admin/vendor-orders/{id}/complete', name: 'admin2_vendor_orders_complete', methods: ['POST'])]
    public function complete(Request $request, int $id): Response
    {
        if (! $this->isCsrfTokenValid('vendor_order_action', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectBack($request);
        }

        $order = $this->vendorOrderRepository->find($id);
        if ($order instanceof VendorOrder) {
            $order->setStatus(VendorOrder::STATUS_COMPLETED);
            $this->entityManager->flush();
            $this->addFlash('success', 'Замовлення постачальника позначено як реалізоване.');
        }

        return $this->redirectBack($request);
    }

    #[Route('/admin/vendor-orders/{id}/delete', name: 'admin2_vendor_orders_delete', methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        if (! $this->isCsrfTokenValid('vendor_order_action', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Невірний CSRF-токен.');

            return $this->redirectBack($request);
        }

        $order = $this->vendorOrderRepository->find($id);
        if ($order === null) {
            $this->addFlash('error', 'Замовлення постачальника не знайдено.');

            return $this->redirectBack($request);
        }

        $label = $order->getSupplierOrderNumber() !== ''
            ? $order->getSupplierOrderNumber()
            : ('#' . $order->getId());

        $this->entityManager->remove($order);
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('Замовлення постачальника «%s» видалено.', $label));

        return $this->redirectBack($request);
    }

    private function handleForm(Request $request, VendorOrder $order, bool $isNew): Response
    {
        $form = $this->createForm(VendorOrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($order->getItems()->toArray() as $item) {
                if (trim($item->getTitle()) === '') {
                    $order->removeItem($item);
                }
            }

            $order->syncFromItems();

            if (trim($order->getProductTitle()) === '') {
                $this->addFlash('error', 'Додайте хоча б один товар.');

                return $this->render('admin2/vendor_orders/edit.html.twig', [
                    'vendorOrder' => $order,
                    'form'        => $form,
                    'isNew'       => $isNew,
                    'copyText'    => $isNew ? null : $this->clipboardFormatter->formatVendorOrder($order),
                ]);
            }

            if ($isNew) {
                $this->entityManager->persist($order);
            }
            $this->entityManager->flush();

            $this->addFlash('success', 'Замовлення постачальника збережено.');

            return $this->redirectToRoute('admin2_vendor_orders_edit', ['id' => $order->getId()]);
        }

        return $this->render('admin2/vendor_orders/edit.html.twig', [
            'vendorOrder' => $order,
            'form'        => $form,
            'isNew'       => $isNew,
            'copyText'    => $isNew ? null : $this->clipboardFormatter->formatVendorOrder($order),
        ]);
    }

    private function redirectBack(Request $request): Response
    {
        $redirect = (string) $request->request->get('_redirect', '');
        if ($redirect !== '' && str_starts_with($redirect, '/admin/')) {
            return $this->redirect($redirect);
        }

        return $this->redirectToRoute('admin2_vendor_orders');
    }
}
