<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Http\Controllers\Api;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Symfony\Component\HttpFoundation\Response;
use Tipoff\Authorization\Models\User;
use Tipoff\Checkout\Models\Order;
use Tipoff\Checkout\Models\OrderItem;
use Tipoff\Checkout\Tests\Support\Models\TestSellable;
use Tipoff\Checkout\Tests\TestCase;

class OrderItemControllerTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function index()
    {
        TestSellable::createTable();
        $sellable = TestSellable::factory()->create();

        // First user
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user,
        ]);
        OrderItem::factory()->withSellable($sellable)->count(3)->create([
            'order_id' => $order,
        ]);
        $order->refresh()->save();

        // Second user
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user,
        ]);
        OrderItem::factory()->withSellable($sellable)->count(4)->create([
            'order_id' => $order,
        ]);
        $order->refresh()->save();

        $this->actingAs($user);

        $response = $this->getJson($this->apiUrl('order-items'))
            ->assertOk();

        $this->assertCount(4, $response->json('data'));
    }

    /** @test */
    public function show_order_item_i_own()
    {
        TestSellable::createTable();
        $sellable = TestSellable::factory()->create();

        /** @var Order $order */
        $order = Order::factory()->create();
        OrderItem::factory()->withSellable($sellable)->count(4)->create([
            'order_id' => $order,
        ]);
        $order->refresh()->save();
        $orderItem = $order->orderItems->first();

        $this->actingAs($order->getUser());

        $response = $this->getJson($this->apiUrl("order-items/{$orderItem->id}"))
            ->assertOk();

        $this->assertEquals($orderItem->id, $response->json('data.id'));
    }

    /** @test */
    public function show_order_item_i_dont_own()
    {
        TestSellable::createTable();
        $sellable = TestSellable::factory()->create();

        /** @var Order $order */
        $order = Order::factory()->create();
        OrderItem::factory()->withSellable($sellable)->count(4)->create([
            'order_id' => $order,
        ]);
        $order->refresh()->save();
        $orderItem = $order->orderItems->first();

        $this->actingAs(User::factory()->create());

        $this->getJson($this->apiUrl("order-items/{$orderItem->id}"))
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /** @test */
    public function index_not_logged_in()
    {
        $this->getJson($this->apiUrl('order-items'))
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /** @test */
    public function show_not_logged_in()
    {
        TestSellable::createTable();
        $sellable = TestSellable::factory()->create();

        /** @var Order $order */
        $order = Order::factory()->create();
        OrderItem::factory()->withSellable($sellable)->count(4)->create([
            'order_id' => $order,
        ]);
        $order->refresh()->save();
        $orderItem = $order->orderItems->first();

        $this->getJson($this->apiUrl("order-items/{$orderItem->id}"))
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
