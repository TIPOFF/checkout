<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Feature\Nova;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tipoff\Checkout\Models\Order;
use Tipoff\Checkout\Tests\TestCase;

class OrderResourceTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function index()
    {
        Order::factory()->count(4)->create();

        $this->actingAs(self::createPermissionedUser('view orders', true));

        $response = $this->getJson('nova-api/orders')
            ->assertOk();

        $this->assertCount(4, $response->json('resources'));
    }

    /** @test */
    public function show()
    {
        $order = Order::factory()->create();

        $this->actingAs(self::createPermissionedUser('view orders', true));

        $response = $this->getJson("nova-api/orders/{$order->id}")
            ->assertOk();

        $this->assertEquals($order->id, $response->json('resource.id.value'));
    }
}
