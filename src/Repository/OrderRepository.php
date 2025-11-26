<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\OrderItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Order|null find($id, $lockMode = null, $lockVersion = null)
 * @method Order|null findOneBy(array $criteria, array $orderBy = null)
 * @method Order[]    findAll()
 * @method Order[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly ProductRepository $productRepository,
        private readonly PaymentTypeRepository $paymentTypeRepository,
        private readonly DeliveryTypeRepository $deliveryTypeRepository,
        private readonly NovaPoshtaCityRepository $novaPoshtaCityRepository,
        private readonly NovaPoshtaOfficeRepository $novaPoshtaOfficeRepository
    ) {
        parent::__construct($registry, Order::class);
    }

    public function fill(array $data): Order
    {
        $order = new Order();
        $order->setName($data['name']);
        $order->setSurname($data['surname']);
        $order->setAddress($data['address']);
        $order->setPhone($data['phone']);
        $order->setEmail($data['email']);
        $order->setPaytype($this->paymentTypeRepository->findOneBy(['id' => $data['paytype']]));
        $order->setDeltype($this->deliveryTypeRepository->findOneBy(['id' => $data['deltype']]));
        $order->setStatus(Order::NEW);
        $order->setPaymentStatus(false);
        $order->setComment($data['comment']);
        $order->setTotal($data['total']);
        $order->setUser($data['user']);
        $order->setOrderNumber($data['orderNumber']);
        $order->setSendNotification($data['sendNotification']);
        $order->setNovaPoshtaCity($this->novaPoshtaCityRepository->findOneBy(['ref' => $data['city']]));
        $order->setNovaPoshtaOffice($this->novaPoshtaOfficeRepository->findOneBy(['ref' => $data['office']]));

        foreach ($data['products'] as $item) {
            $product = $this->productRepository->findOneBy(['id' => $item->getId()]);
            $orderItem = new OrderItem();
            $orderItem->setOrder($order);
            $orderItem->setCount($item->count);
            $orderItem->setProduct($product);
            $orderItem->setPrice($product->getPrice());

            $order->addItem($orderItem);
        }

        return $order;
    }

    public function create(Order $order): Order
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($order);
        $entityManager->flush();

        return $order;
    }

    public function update(Order $order)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->merge($order);
        $entityManager->flush();
    }
}
