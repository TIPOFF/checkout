<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Enums;

use Tipoff\Checkout\Enums\OrderStatus;
use Tipoff\Checkout\Models\Order;
use Tipoff\Checkout\Tests\TestCase;
use Tipoff\Statuses\Models\Status;
use ReflectionClass;

class OrderStatusTest extends TestCase
{
    /** @test */
    public function order_status_has_constats()
    {
        $orderStatus = new ReflectionClass(OrderStatus::class);

        $this->assertArrayHasKey('PROCESSING', $orderStatus->getConstants());
        $this->assertArrayHasKey('SHIPPING', $orderStatus->getConstants());
        $this->assertArrayHasKey('DELIVERING', $orderStatus->getConstants());
        $this->assertArrayHasKey('REALIZING', $orderStatus->getConstants());
        $this->assertArrayHasKey('COMPLETE', $orderStatus->getConstants());
        $this->assertEquals('Processing', $orderStatus->getConstant('PROCESSING'), 'Test that the order status PROCESSING was not changed');
        $this->assertEquals('Shipping', $orderStatus->getConstant('SHIPPING'), 'Test that the order status SHIPPING was not changed');
        $this->assertEquals('Delivering', $orderStatus->getConstant('DELIVERING'), 'Test that the order status DELIVERING was not changed');
        $this->assertEquals('Realizing', $orderStatus->getConstant('REALIZING'), 'Test that the order status REALIZING was not changed');
        $this->assertEquals('Complete', $orderStatus->getConstant('COMPLETE'), 'Test that the order status COMPLETE was not changed');
    }

    /** @test */
    public function can_casting_to_status()
    {
        /** @var Order $order */
        $order = Order::factory()->create();
        $order->setOrderStatus(OrderStatus::PROCESSING());

        /** @var Order $orderStatus */
        $orderStatus = $order->getOrderStatus();

        $result = $orderStatus->toStatus();

        $this->assertInstanceOf(Status::class, $result);
    }
}
