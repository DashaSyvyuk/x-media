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
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @group integration
 * @group repository
 */
class OrderRepositoryTest extends KernelTestCase
{
    private OrderRepository $orderRepository;
    private ProductRepository $productRepository;
    private PaymentTypeRepository $paymentTypeRepository;
    private DeliveryTypeRepository $deliveryTypeRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        $this->orderRepository = $container->get(OrderRepository::class);
        $this->productRepository = $container->get(ProductRepository::class);
        $this->paymentTypeRepository = $container->get(PaymentTypeRepository::class);
        $this->deliveryTypeRepository = $container->get(DeliveryTypeRepository::class);
    }

    public function testFillMethodCreatesOrderFromArrayData(): void
    {
        // Create test product
        $product = new Product();
        $product->setTitle('Test Product');
        $product->setStatus(Product::STATUS_ACTIVE);
        $product->setAvailability(Product::AVAILABILITY_AVAILABLE);
        $product->setPrice(1000);
        $product->setProductCode('TEST-001');
        $product->count = 2;
        
        $entityManager = $this->orderRepository->getEntityManager();
        $entityManager->persist($product);
        $entityManager->flush();
        
        // Create test payment and delivery types
        $paymentType = new PaymentType();
        $paymentType->setTitle('Test Payment');
        $entityManager->persist($paymentType);
        
        $deliveryType = new DeliveryType();
        $deliveryType->setTitle('Test Delivery');
        $entityManager->persist($deliveryType);
        
        $entityManager->flush();
        
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
        
        // Clean up
        $entityManager->remove($product);
        $entityManager->remove($paymentType);
        $entityManager->remove($deliveryType);
        $entityManager->flush();
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

