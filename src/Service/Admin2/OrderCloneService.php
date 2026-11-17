<?php

namespace App\Service\Admin2;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Repository\OrderRepository;
use App\Utils\OrderNumber;
use Doctrine\ORM\EntityManagerInterface;

readonly class OrderCloneService
{
    public function __construct(
        private OrderRepository $orderRepository,
        private OrderNumber $orderNumber,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function clone(int $orderId): ?Order
    {
        $source = $this->orderRepository->find($orderId);
        if ($source === null) {
            return null;
        }

        $clone = new Order();
        $clone->setOrderNumber($this->orderNumber->generateOrderNumber());
        $clone->setName($source->getName());
        $clone->setSurname($source->getSurname());
        $clone->setAddress($source->getAddress());
        $clone->setNovaPoshtaCity($source->getNovaPoshtaCity());
        $clone->setNovaPoshtaOffice($source->getNovaPoshtaOffice());
        $clone->setPhone($source->getPhone());
        $clone->setEmail($source->getEmail());
        $clone->setPaytype($source->getPaytype());
        $clone->setDeltype($source->getDeltype());
        $clone->setComment($source->getComment());
        $clone->setSource((string) ($source->getSource() ?? 'Admin'));
        $clone->setLabels($source->getLabels());
        $clone->setUser($source->getUser());
        $clone->setTotal($source->getTotal());
        $clone->setSendNotification(false);
        $clone->setTtn(null);

        // Fresh lifecycle: new number/date, never copy paid flag.
        // COMPLETED would force paymentStatus=true via entity callbacks.
        $status = $source->getStatus();
        if ($status === '' || $status === Order::COMPLETED) {
            $status = Order::NEW;
        }
        $clone->setStatus($status);
        $clone->setPaymentStatus(false);

        foreach ($source->getItems() as $item) {
            $copy = new OrderItem();
            $copy->setProduct($item->getProduct());
            $copy->setCount($item->getCount());
            $copy->setPrice($item->getPrice());
            $clone->addItem($copy);
        }

        $this->entityManager->persist($clone);
        $this->entityManager->flush();

        return $clone;
    }
}
