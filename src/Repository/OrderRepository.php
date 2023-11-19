<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\OrderItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Order|null find($id, $lockMode = null, $lockVersion = null)
 * @method Order|null findOneBy(array $criteria, array $orderBy = null)
 * @method Order[]    findAll()
 * @method Order[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderRepository extends ServiceEntityRepository
{
    private ProductRepository $productRepository;
    private PaymentTypeRepository $paymentTypeRepository;
    private DeliveryTypeRepository $deliveryTypeRepository;

    public function __construct(ManagerRegistry $registry, ProductRepository $productRepository, PaymentTypeRepository $paymentTypeRepository, DeliveryTypeRepository $deliveryTypeRepository)
    {
        parent::__construct($registry, Order::class);
        $this->productRepository = $productRepository;
        $this->paymentTypeRepository = $paymentTypeRepository;
        $this->deliveryTypeRepository = $deliveryTypeRepository;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function create(array $data): Order
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

        foreach ($data['products'] as $item) {
            $orderItem = new OrderItem();
            $orderItem->setOrder($order);
            $orderItem->setCount($item->count);
            $orderItem->setProduct($this->productRepository->findOneBy(['id' => $item->getId()]));

            $order->addItem($orderItem);
        }
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
