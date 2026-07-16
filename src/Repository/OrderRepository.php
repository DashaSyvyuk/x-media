<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\OrderItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
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

    /**
     * @param array<string, mixed> $data
     */
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

    public function update(Order $order): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->refresh($order);
        $entityManager->flush();
    }

    public function createAdminListQueryBuilder(
        string $search = '',
        ?string $status = null,
        string $sort = 'id',
        string $direction = 'DESC',
        ?\DateTimeImmutable $dateFrom = null,
        ?\DateTimeImmutable $dateTo = null,
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('o')
            ->leftJoin('o.paytype', 'paytype')->addSelect('paytype')
            ->leftJoin('o.deltype', 'deltype')->addSelect('deltype');

        $search = trim($search);
        if ($search !== '') {
            $qb->andWhere(
                'LOWER(o.orderNumber) LIKE :search
                OR LOWER(o.surname) LIKE :search
                OR LOWER(o.phone) LIKE :search
                OR LOWER(o.email) LIKE :search
                OR LOWER(o.name) LIKE :search',
            )->setParameter('search', '%' . mb_strtolower($search) . '%');
        }

        if ($status !== null && $status !== '') {
            $qb->andWhere('o.status = :status')->setParameter('status', $status);
        }

        if ($dateFrom !== null) {
            $qb->andWhere('o.createdAt >= :dateFrom')->setParameter('dateFrom', $dateFrom);
        }

        if ($dateTo !== null) {
            $qb->andWhere('o.createdAt <= :dateTo')->setParameter('dateTo', $dateTo);
        }

        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
        $allowedSorts = ['id', 'orderNumber', 'surname', 'phone', 'status', 'total', 'createdAt'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'id';
        }

        $qb->orderBy('o.' . $sort, $direction);

        return $qb;
    }

    /**
     * @return array{
     *     total: int,
     *     new: int,
     *     inProgress: int,
     *     shipped: int,
     *     completed: int,
     *     canceled: int,
     *     totalSum: int
     * }
     */
    public function getStatusSummary(
        ?string $statusFilter = null,
        ?\DateTimeImmutable $dateFrom = null,
        ?\DateTimeImmutable $dateTo = null,
    ): array {
        $qb = $this->createQueryBuilder('o')
            ->select('o.status', 'COUNT(o.id) AS cnt', 'COALESCE(SUM(o.total), 0) AS sumTotal');

        if ($statusFilter !== null && $statusFilter !== '') {
            $qb->andWhere('o.status = :status')->setParameter('status', $statusFilter);
        }

        if ($dateFrom !== null) {
            $qb->andWhere('o.createdAt >= :dateFrom')->setParameter('dateFrom', $dateFrom);
        }

        if ($dateTo !== null) {
            $qb->andWhere('o.createdAt <= :dateTo')->setParameter('dateTo', $dateTo);
        }

        $rows = $qb->groupBy('o.status')->getQuery()->getScalarResult();

        $summary = [
            'total'      => 0,
            'new'        => 0,
            'inProgress' => 0,
            'shipped'    => 0,
            'completed'  => 0,
            'canceled'   => 0,
            'totalSum'   => 0,
        ];

        foreach ($rows as $row) {
            $count = (int) $row['cnt'];
            $sum   = (int) $row['sumTotal'];
            $summary['total']    += $count;
            $summary['totalSum'] += $sum;

            $groupId = Order::GROUPED_STATUSES[$row['status']]['id'] ?? 0;
            match ($groupId) {
                1       => $summary['new'] += $count,
                2       => $summary['inProgress'] += $count,
                3       => $summary['shipped'] += $count,
                4       => $summary['completed'] += $count,
                5       => $summary['canceled'] += $count,
                default => null,
            };
        }

        return $summary;
    }

    /**
     * @return Order[]
     */
    public function findActiveForFulfillmentBoard(int $limit = 100): array
    {
        $statuses = [
            Order::NEW,
            Order::NOT_APPROVED,
            Order::APPROVED,
            Order::PACKING,
        ];

        return $this->createQueryBuilder('o')
            ->leftJoin('o.items', 'items')->addSelect('items')
            ->leftJoin('items.product', 'product')->addSelect('product')
            ->leftJoin('o.novaPoshtaCity', 'novaPoshtaCity')->addSelect('novaPoshtaCity')
            ->leftJoin('o.novaPoshtaOffice', 'novaPoshtaOffice')->addSelect('novaPoshtaOffice')
            ->where('o.status IN (:statuses)')
            ->setParameter('statuses', $statuses)
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Local orders currently in delivery (courier or Nova Poshta).
     *
     * @return Order[]
     */
    public function findShippingOrders(int $limit = 150): array
    {
        $statuses = [
            Order::NOVA_POSHTA_DELIVERING,
            Order::COURIER_DELIVERING,
        ];

        return $this->createQueryBuilder('o')
            ->leftJoin('o.items', 'items')->addSelect('items')
            ->leftJoin('items.product', 'product')->addSelect('product')
            ->leftJoin('o.novaPoshtaCity', 'novaPoshtaCity')->addSelect('novaPoshtaCity')
            ->leftJoin('o.novaPoshtaOffice', 'novaPoshtaOffice')->addSelect('novaPoshtaOffice')
            ->where('o.status IN (:statuses)')
            ->setParameter('statuses', $statuses)
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
