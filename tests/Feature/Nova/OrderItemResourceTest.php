<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Feature\Nova;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tipoff\Checkout\Models\OrderItem;
use Tipoff\Checkout\Tests\Support\Models\TestSellable;
use Tipoff\Checkout\Tests\TestCase;

class OrderItemResourceTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function index()
    {
        TestSellable::createTable();
        $sellable = TestSellable::factory()->create();

        OrderItem::factory()->count(4)->withSellable($sellable)->create();

        $this->actingAs(self::createPermissionedUser('view order items', true));

        $response = $this->getJson('nova-api/order-items')
            ->assertOk();

        $this->assertCount(4, $response->json('resources'));
    }
}
