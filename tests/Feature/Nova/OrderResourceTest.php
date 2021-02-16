<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Feature\Nova;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Tipoff\Checkout\Models\Order;
use Tipoff\Checkout\Tests\TestCase;
use Tipoff\TestSupport\Models\User;

class OrderResourceTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        $this->stubNovaResources = false;

        parent::setUp();
    }

    /** @test */
    public function create()
    {
        $this->actingAs(User::factory()->create());

        $this->getJson('nova-api/orders/creation-fields')
            ->assertStatus(403);
    }

    /** @test */
    public function edit()
    {
        $this->actingAs(User::factory()->create());

        $this->getJson('nova-api/orders/creation-fields')
            ->assertStatus(403);
    }

    /** @test */
    public function index_no_user()
    {
        $this->getJson('nova-api/orders')
            ->assertStatus(401);
    }

    /** @test */
    public function detail_no_user()
    {
        /** @var Order $order */
        $order = Order::factory()->create();

        $this->getJson("nova-api/orders/{$order->id}")
            ->assertStatus(401);
    }

    /** @test */
    public function index_without_stub_resources()
    {
        Order::factory()->count(4)->create();

        $this->actingAs(User::factory()->create());

        $response = $this->getJson('nova-api/orders')
            ->assertOk();

        $this->assertCount(4, $response->json('resources'));

        $orderFields = collect($response->json('resources')[0]['fields'])->pluck('attribute');

        $this->assertCount(4, $orderFields);
        $this->assertEquals([
            'id',
            'order_number',
            'amount',
            'created_at',
        ], $orderFields->toArray());
    }

    /** @test */
    public function detail_with_no_stubs()
    {
        /** @var Order $order */
        $order = Order::factory()->create();

        $this->actingAs(User::factory()->create());

        $response = $this->getJson("nova-api/orders/{$order->id}")
            ->assertOk();

        $this->assertEquals($order->order_number, $response->json('title'));
        $this->assertCount(2, $response->json('panels'));

        $orderFields = collect($response->json('resource')['fields'])
            ->groupBy('panel')
            ->map(function (Collection $panelItems) {
                return $panelItems->pluck('attribute')->toArray();
            })
            ->toArray();

        $this->assertCount(2, $orderFields);
        $this->assertEquals(["Order Details: {$order->order_number}", "Data Fields"], array_keys($orderFields));

        $this->assertEquals([
            'order_number',
            'amount',
            'total_taxes',
            'total_fees',
            'bookings', // TODO - remove when tipoff/bookings dependency is eliminated
        ], $orderFields["Order Details: {$order->order_number}"]);

        $this->assertEquals([
            'id',
            'updated_at',
        ], $orderFields["Data Fields"]);
    }
}
