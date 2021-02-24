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

    /** @test */
    public function index()
    {
        $this->markTestSkipped('PENDING UPDATE FOR NEW orders / order_items');

        Order::factory()->count(4)->create();

        $this->actingAs(User::factory()->create());

        $response = $this->getJson('nova-api/orders')
            ->assertOk();

        $this->assertCount(4, $response->json('resources'));
    }
}
