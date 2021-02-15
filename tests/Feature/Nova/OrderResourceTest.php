<?php

declare(strict_types=1);

namespace Tipoff\Discounts\Tests\Feature\Nova;

use Illuminate\Foundation\Testing\DatabaseTransactions;
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
            'created_at'
        ], $orderFields->toArray());
    }

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
            'created_at'
        ], $orderFields->toArray());
    }

}
