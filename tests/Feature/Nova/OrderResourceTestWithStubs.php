<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Feature\Nova;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Tipoff\Checkout\Models\Order;
use Tipoff\Checkout\Tests\TestCase;
use Tipoff\TestSupport\Models\User;

class OrderResourceTestWithStubs extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function index_with_stub_resources()
    {
        $this->createStubNovaResources();

        Order::factory()->count(4)->create();

        $this->actingAs(User::factory()->create());

        $response = $this->getJson('nova-api/orders')
            ->assertOk();

        $this->assertCount(4, $response->json('resources'));

        $orderFields = collect($response->json('resources')[0]['fields'])->pluck('attribute');

        $this->assertCount(6, $orderFields);
        $this->assertEquals([
            'id',
            'order_number',
            'customer',
            'location',
            'amount',
            'created_at',
        ], $orderFields->toArray());
    }

    /** @test */
    public function detail_with_stubs()
    {
        $this->createStubNovaResources();

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
            'customer',
            'location',
            'amount',
            'total_taxes',
            'total_fees',
            'bookings',
            'purchasedVouchers',
            'payments',
            'invoices',
            'discounts',
            'voucher',
            'notes',
        ], $orderFields["Order Details: {$order->order_number}"]);

        $this->assertEquals([
            'id',
            'creator',
            'created_at',
            'updated_at',
        ], $orderFields["Data Fields"]);
    }
}
