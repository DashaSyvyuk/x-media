<?php

namespace App\Controller\Admin2;

use App\Entity\Order;
use App\Form\Admin2\OrderType;
use App\Repository\OrderRepository;
use App\Service\Admin2\OrderClipboardFormatter;
use App\Service\Admin2\OrderStatusHelper;
use App\Utils\OrderNumber;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Security("is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_ADMIN')")]
class OrderEditController extends AbstractController
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly OrderStatusHelper $orderStatusHelper,
        private readonly OrderClipboardFormatter $clipboardFormatter,
        private readonly OrderNumber $orderNumber,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin2/orders/new', name: 'admin2_orders_new', methods: ['GET', 'POST'])]
    public function createNew(Request $request): Response
    {
        $order = new Order();
        $order->setOrderNumber($this->orderNumber->generateOrderNumber());
        $order->setStatus(Order::NEW);
        $order->setPaymentStatus(false);
        $order->setSendNotification(false);
        $order->setSource('Admin');
        $order->setTotal(0);

        $form = $this->createForm(OrderType::class, $order, [
            'status_choices' => $this->orderStatusHelper->getAvailableStatuses(null),
            'is_edit'        => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->recalculateTotal($order);
            $this->entityManager->persist($order);
            $this->entityManager->flush();

            $this->addFlash('success', sprintf('Замовлення #%s створено.', $order->getOrderNumber()));

            return $this->redirectToRoute('admin2_orders_edit', ['id' => $order->getId()]);
        }

        return $this->render('admin2/orders/edit.html.twig', [
            'order'  => $order,
            'form'   => $form,
            'isNew'  => true,
        ]);
    }

    #[Route('/admin2/orders/{id}/edit', name: 'admin2_orders_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $order = $this->orderRepository->find($id);
        if (! $order instanceof Order) {
            throw $this->createNotFoundException('Замовлення не знайдено.');
        }

        $form = $this->createForm(OrderType::class, $order, [
            'status_choices' => $this->orderStatusHelper->getAvailableStatuses($order),
            'is_edit'        => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->recalculateTotal($order);
            $this->entityManager->flush();
            $this->addFlash('success', sprintf('Замовлення #%s збережено.', $order->getOrderNumber()));

            return $this->redirectToRoute('admin2_orders_edit', ['id' => $order->getId()]);
        }

        return $this->render('admin2/orders/edit.html.twig', [
            'order'    => $order,
            'form'     => $form,
            'isNew'    => false,
            'copyText' => $this->clipboardFormatter->formatLocalOrder($order),
        ]);
    }

    private function recalculateTotal(Order $order): void
    {
        $total = 0;
        foreach ($order->getItems() as $item) {
            $total += ($item->getPrice() ?? 0) * $item->getCount();
        }
        $order->setTotal($total);
    }
}
