<?php

namespace App\Tests\Unit\Entity;

use App\Entity\DeliveryType;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\PaymentType;
use App\Entity\User;
use DateTime;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group entity
 */
class OrderTest extends TestCase
{
    public function testOrderCreationWithDefaultValues(): void
    {
        $order = new Order();

        $this->assertSame(0, $order->getId());
        $this->assertSame("", $order->getOrderNumber());
        $this->assertSame("", $order->getName());
        $this->assertSame("", $order->getSurname());
        $this->assertSame(0, $order->getTotal());
        $this->assertCount(0, $order->getItems());
    }

    public function testOrderSettersAndGetters(): void
    {
        $order = new Order();
        $order->setOrderNumber('1234567890');
        $order->setName('John');
        $order->setSurname('Doe');
        $order->setPhone('+380501234567');
        $order->setEmail('john@example.com');
        $order->setAddress('Test Address');
        $order->setTotal(1000);
        $order->setStatus(Order::NEW);
        $order->setPaymentStatus(false);

        $this->assertSame('1234567890', $order->getOrderNumber());
        $this->assertSame('John', $order->getName());
        $this->assertSame('Doe', $order->getSurname());
        $this->assertSame('+380501234567', $order->getPhone());
        $this->assertSame('john@example.com', $order->getEmail());
        $this->assertSame('Test Address', $order->getAddress());
        $this->assertSame(1000, $order->getTotal());
        $this->assertSame(Order::NEW, $order->getStatus());
        $this->assertFalse($order->getPaymentStatus());
    }

    public function testOrderItemsManagement(): void
    {
        $order = new Order();
        $orderItem = new OrderItem();

        $order->addItem($orderItem);
        $this->assertCount(1, $order->getItems());
        $this->assertSame($order, $orderItem->getOrder());

        $order->addItem($orderItem);
        $this->assertCount(1, $order->getItems());

        $order->removeItem($orderItem);
        $this->assertCount(0, $order->getItems());
    }

    public function testOrderStatusConstants(): void
    {
        $this->assertSame('new', Order::NEW);
        $this->assertSame('not_approved', Order::NOT_APPROVED);
        $this->assertSame('confirmed', Order::APPROVED);
        $this->assertSame('completed', Order::COMPLETED);
        $this->assertSame('canceled_not_confirmed', Order::CANCELED_NOT_CONFIRMED);
    }

    public function testOrderStatusesArray(): void
    {
        $statuses = Order::STATUSES;

        $this->assertArrayHasKey(Order::NEW, $statuses);
        $this->assertArrayHasKey(Order::APPROVED, $statuses);
        $this->assertArrayHasKey(Order::COMPLETED, $statuses);
        $this->assertCount(10, $statuses);
    }

    public function testOrderGroupedStatuses(): void
    {
        $groupedStatuses = Order::GROUPED_STATUSES;

        $this->assertArrayHasKey(Order::NEW, $groupedStatuses);
        $this->assertArrayHasKey('id', $groupedStatuses[Order::NEW]);
        $this->assertArrayHasKey('color', $groupedStatuses[Order::NEW]);
        $this->assertArrayHasKey('title', $groupedStatuses[Order::NEW]);

        $this->assertEquals(
            $groupedStatuses[Order::NEW]['id'],
            $groupedStatuses[Order::NOT_APPROVED]['id']
        );
    }

    public function testOrderLabels(): void
    {
        $order = new Order();
        $labels = [Order::LABEL_XMEDIA, Order::LABEL_PROM, Order::LABEL_ROZETKA];

        $order->setLabels($labels);
        $this->assertSame($labels, $order->getLabels());
        $this->assertCount(3, $order->getLabels());
    }

    public function testOrderUserRelationship(): void
    {
        $order = new Order();
        $user = new User();

        $order->setUser($user);
        $this->assertSame($user, $order->getUser());
    }

    public function testOrderPaymentAndDeliveryTypes(): void
    {
        $order = new Order();
        $paymentType = new PaymentType();
        $deliveryType = new DeliveryType();

        $order->setPaytype($paymentType);
        $order->setDeltype($deliveryType);

        $this->assertSame($paymentType, $order->getPaytype());
        $this->assertSame($deliveryType, $order->getDeltype());
    }

    public function testOrderDateFields(): void
    {
        $order = new Order();
        $createdAt = new DateTime('2024-01-15 10:30:00');
        $updatedAt = new DateTime('2024-01-15 11:30:00');

        $order->setCreatedAt($createdAt);
        $order->setUpdatedAt($updatedAt);

        $this->assertSame($createdAt, $order->getCreatedAt());
        $this->assertSame($updatedAt, $order->getUpdatedAt());
    }
}
