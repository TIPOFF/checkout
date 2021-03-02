<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Feature\Nova;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tipoff\Authorization\Models\User;
use Tipoff\Checkout\Models\OrderItem;
use Tipoff\Checkout\Tests\Support\Models\TestSellable;
use Tipoff\Checkout\Tests\TestCase;

class OrderItemResourceTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function index()
    {
        $this->markTestSkipped('NEED TO PERMISSION REAL AUTH USER PROPERLY NOW');

        TestSellable::createTable();
        $sellable = TestSellable::factory()->create();

        OrderItem::factory()->count(4)->withSellable($sellable)->create();

        $this->actingAs(User::factory()->create());

        $response = $this->getJson('nova-api/order-items')
            ->assertOk();

        $this->assertCount(4, $response->json('resources'));
    }
}
