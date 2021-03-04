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

class OrderControllerTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function index()
    {
        // First user
        $user = User::factory()->create();
        Order::factory()->count(4)->create([
            'user_id' => $user,
        ]);

        // Second user
        $user = User::factory()->create();
        Order::factory()->count(4)->create([
            'user_id' => $user,
        ]);

        $this->actingAs($user);

        $response = $this->getJson('tipoff/orders')
            ->assertOk();

        $this->assertCount(4, $response->json('data'));
    }

    /** @test */
    public function show_order_i_own()
    {
        TestSellable::createTable();
        $sellable = TestSellable::factory()->create();

        /** @var Order $order */
        $order = Order::factory()->create();
        OrderItem::factory()->withSellable($sellable)->count(4)->create([
            'order_id' => $order,
        ]);
        $order->refresh()->save();

        $this->actingAs($order->getUser());

        $response = $this->getJson("tipoff/orders/{$order->id}")
            ->assertOk();

        $this->assertEquals($order->order_number, $response->json('data.order_number'));
        $this->assertNull($response->json('data.items'));
    }

    /** @test */
    public function show_order_i_own_include_items()
    {
        TestSellable::createTable();
        $sellable = TestSellable::factory()->create();

        /** @var Order $order */
        $order = Order::factory()->create();
        OrderItem::factory()->withSellable($sellable)->count(4)->create([
            'order_id' => $order,
        ]);
        $order->refresh()->save();

        $this->actingAs($order->getUser());

        $response = $this->getJson("tipoff/orders/{$order->id}?include=items")
            ->assertOk();

        $this->assertEquals($order->order_number, $response->json('data.order_number'));
        // Ensure items
        $this->assertCount(4, $response->json('data.items.data'));
        // Ensure sellable data in items
        $this->assertNotNull($response->json('data.items.data.0.sellable.data'));
    }

    /** @test */
    public function show_order_i_dont_own()
    {
        /** @var Order $order */
        $order = Order::factory()->create();

        $this->actingAs(User::factory()->create());

        $this->getJson("tipoff/orders/{$order->id}")
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /** @test */
    public function index_not_logged_in()
    {
        $this->getJson('tipoff/orders')
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /** @test */
    public function show_not_logged_in()
    {
        /** @var Order $order */
        $order = Order::factory()->create();

        $this->getJson("tipoff/orders/{$order->id}")
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
