<?php

declare(strict_types=1);

namespace Tipoff\Discounts\Tests\Feature\Nova;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tipoff\Checkout\Models\OrderItem;
use Tipoff\Checkout\Tests\Support\Models\TestSellable;
use Tipoff\Checkout\Tests\TestCase;
use Tipoff\TestSupport\Models\User;

class OrderItemResourceTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function index()
    {
        $this->logToStderr($this->app);
        TestSellable::createTable();
        $sellable = TestSellable::factory()->create();

        OrderItem::factory()->count(4)->withSellable($sellable)->create();

        $this->actingAs(User::factory()->create());

        $response = $this->getJson('nova-api/order-items')
            ->assertOk();

        $this->assertCount(4, $response->json('resources'));
    }
}
