<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tipoff\Authorization\Models\User;
use Tipoff\Checkout\Enums\OrderStatus;
use Tipoff\Checkout\Filters\ItemFilter;
use Tipoff\Checkout\Models\Cart;
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

    /** @test */
    public function test_get_cart()
    {
        /** @var Order $order */
        $order = Order::factory()->create();
        Cart::factory()->create(['order_id' => $order->id]);

        $cart = $order->cart();

        $this->assertInstanceOf(HasOne::class, $cart);
        $this->assertEquals('order_id', $cart->getForeignKeyName());
        $this->assertEquals('orders.id', $cart->getQualifiedParentKeyName());
        $this->assertEquals($order->id, $cart->first()->order_id);
    }

    /** @test */
    public function test_get_item_filter()
    {
        /** @var Order $order */
        $order = Order::factory()->create();

        $this->assertInstanceOf(ItemFilter::class, $order->itemFilter());
    }
}
