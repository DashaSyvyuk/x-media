<?php

namespace App\Tests\Integration\Repository;

use App\Entity\DeliveryType;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\PaymentType;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\DeliveryTypeRepository;
use App\Repository\NovaPoshtaCityRepository;
use App\Repository\NovaPoshtaOfficeRepository;
use App\Repository\OrderRepository;
use App\Repository\PaymentTypeRepository;
use App\Repository\ProductRepository;
use App\Tests\Traits\FixturesTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @group integration
 * @group repository
 */
class OrderRepositoryTest extends KernelTestCase
{
    use FixturesTrait;

    private OrderRepository $orderRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->loadFixtures();
        $container = static::getContainer();

        $this->orderRepository = $container->get(OrderRepository::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Restore exception handler to avoid risky test warnings
        restore_exception_handler();
    }

    public function testFillMethodCreatesOrderFromArrayData(): void
    {
        $entityManager = $this->orderRepository->getEntityManager();

        // Use product from fixtures (MacBook Pro)
        $productRepository = $entityManager->getRepository(Product::class);
        $product = $productRepository->findOneBy(['productCode' => 'MBP-16-001']);
        $this->assertNotNull($product);
        $product->count = 2;

        // Use payment and delivery types from fixtures
        $paymentTypeRepository = $entityManager->getRepository(PaymentType::class);
        $paymentType = $paymentTypeRepository->findOneBy(['title' => 'Готівка']);
        $this->assertNotNull($paymentType);

        $deliveryTypeRepository = $entityManager->getRepository(DeliveryType::class);
        $deliveryType = $deliveryTypeRepository->findOneBy(['title' => 'Нова Пошта']);
        $this->assertNotNull($deliveryType);

        $data = [
            'name' => 'John',
            'surname' => 'Doe',
            'address' => 'Test Address',
            'phone' => '+380501234567',
            'email' => 'john@example.com',
            'paytype' => $paymentType->getId(),
            'deltype' => $deliveryType->getId(),
            'comment' => 'Test comment',
            'total' => 2000,
            'user' => null,
            'orderNumber' => '1234567890',
            'sendNotification' => true,
            'city' => null,
            'office' => null,
            'products' => [$product],
        ];

        $order = $this->orderRepository->fill($data);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertSame('John', $order->getName());
        $this->assertSame('Doe', $order->getSurname());
        $this->assertSame('+380501234567', $order->getPhone());
        $this->assertSame('john@example.com', $order->getEmail());
        $this->assertSame(2000, $order->getTotal());
        $this->assertSame(Order::NEW, $order->getStatus());
        $this->assertFalse($order->getPaymentStatus());
        $this->assertCount(1, $order->getItems());
    }

    public function testCreateMethodPersistsOrderToDatabase(): void
    {
        $order = new Order();
        $order->setOrderNumber('TEST-' . time());
        $order->setName('Jane');
        $order->setSurname('Smith');
        $order->setPhone('+380671234567');
        $order->setEmail('jane@example.com');
        $order->setStatus(Order::NEW);
        $order->setPaymentStatus(false);
        $order->setTotal(3000);

        $createdOrder = $this->orderRepository->create($order);

        $this->assertNotNull($createdOrder->getId());
        $this->assertGreaterThan(0, $createdOrder->getId());

        // Verify order was persisted
        $foundOrder = $this->orderRepository->find($createdOrder->getId());
        $this->assertNotNull($foundOrder);
        $this->assertSame('Jane', $foundOrder->getName());
        $this->assertSame(3000, $foundOrder->getTotal());

        // Clean up
        $entityManager = $this->orderRepository->getEntityManager();
        $entityManager->remove($foundOrder);
        $entityManager->flush();
    }

    public function testFindOneByOrderNumber(): void
    {
        $uniqueOrderNumber = 'UNIQUE-' . time();

        $order = new Order();
        $order->setOrderNumber($uniqueOrderNumber);
        $order->setName('Test');
        $order->setSurname('User');
        $order->setPhone('+380501111111');
        $order->setEmail('test@example.com');
        $order->setStatus(Order::NEW);
        $order->setPaymentStatus(false);
        $order->setTotal(1500);

        $this->orderRepository->create($order);

        $foundOrder = $this->orderRepository->findOneBy(['orderNumber' => $uniqueOrderNumber]);

        $this->assertNotNull($foundOrder);
        $this->assertSame($uniqueOrderNumber, $foundOrder->getOrderNumber());
        $this->assertSame('Test', $foundOrder->getName());

        // Clean up
        $entityManager = $this->orderRepository->getEntityManager();
        $entityManager->remove($foundOrder);
        $entityManager->flush();
    }
}
