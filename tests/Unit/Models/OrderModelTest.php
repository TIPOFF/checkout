<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Models;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tipoff\Authorization\Models\User;
use Tipoff\Checkout\Enums\OrderStatus;
use Tipoff\Checkout\Models\Order;
use Tipoff\Checkout\Tests\TestCase;
use Tipoff\Statuses\Models\StatusRecord;

class OrderModelTest extends TestCase
{
    use DatabaseTransactions;
    use WithFaker;

    /** @test */
    public function create()
    {
        $model = Order::factory()->create();
        $this->assertNotNull($model);
    }

    /** @test */
    public function can_set_status()
    {
        $this->actingAs(User::factory()->create());

        /** @var Order $order */
        $order = Order::factory()->create();
        $order->setOrderStatus(OrderStatus::PROCESSING());
        $this->assertEquals(OrderStatus::PROCESSING, $order->getOrderStatus()->getValue());

        $order->setOrderStatus(OrderStatus::SHIPPING());
        $this->assertEquals(OrderStatus::SHIPPING, $order->getOrderStatus()->getValue());

        $order->setOrderStatus(OrderStatus::DELIVERING());
        $this->assertEquals(OrderStatus::DELIVERING, $order->getOrderStatus()->getValue());

        $order->setOrderStatus(OrderStatus::DELIVERING());

        $history = $order->getOrderStatusHistory()
            ->map(function (StatusRecord $statusRecord) {
                return (string) $statusRecord->status;
            })->toArray();

        $this->assertEquals([OrderStatus::DELIVERING, OrderStatus::SHIPPING, OrderStatus::PROCESSING], $history);
    }
}
