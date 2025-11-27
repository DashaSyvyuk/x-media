<?php

namespace App\Tests\Unit\Utils;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Utils\OrderNumber;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group utils
 */
class OrderNumberTest extends TestCase
{
    public function testGenerateOrderNumberReturnsTimestamp(): void
    {
        $orderRepository = $this->createMock(OrderRepository::class);
        $orderRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['orderNumber' => $this->isType('string')])
            ->willReturn(null);
        
        $orderNumber = new OrderNumber($orderRepository);
        $generatedNumber = $orderNumber->generateOrderNumber();
        
        $this->assertIsString($generatedNumber);
        $this->assertGreaterThan(0, (int) $generatedNumber);
        $this->assertMatchesRegularExpression('/^\d+$/', $generatedNumber);
    }

    public function testGenerateOrderNumberHandlesCollision(): void
    {
        $orderRepository = $this->createMock(OrderRepository::class);
        
        // First call returns an order (collision), second call returns null
        $orderRepository->expects($this->exactly(2))
            ->method('findOneBy')
            ->willReturnCallback(function ($criteria) {
                static $callCount = 0;
                $callCount++;
                
                if ($callCount === 1) {
                    $order = new Order();
                    $order->setOrderNumber($criteria['orderNumber']);
                    return $order;
                }
                
                return null;
            });
        
        $orderNumber = new OrderNumber($orderRepository);
        $generatedNumber = $orderNumber->generateOrderNumber();
        
        $this->assertIsString($generatedNumber);
        $this->assertGreaterThan(0, (int) $generatedNumber);
    }

    public function testGenerateOrderNumberCreatesUniqueNumbers(): void
    {
        $orderRepository = $this->createMock(OrderRepository::class);
        $orderRepository->method('findOneBy')->willReturn(null);
        
        $orderNumber = new OrderNumber($orderRepository);
        
        $number1 = $orderNumber->generateOrderNumber();
        usleep(100); // Small delay to ensure different timestamps
        $number2 = $orderNumber->generateOrderNumber();
        
        $this->assertNotEquals($number1, $number2);
    }

    public function testGenerateOrderNumberLengthIsConsistent(): void
    {
        $orderRepository = $this->createMock(OrderRepository::class);
        $orderRepository->method('findOneBy')->willReturn(null);
        
        $orderNumber = new OrderNumber($orderRepository);
        $generatedNumber = $orderNumber->generateOrderNumber();
        
        // Timestamp should be 10 digits (current Unix timestamp)
        $this->assertEquals(10, strlen($generatedNumber));
    }
}

