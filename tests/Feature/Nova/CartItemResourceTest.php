<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Feature\Nova;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tipoff\Authorization\Models\User;
use Tipoff\Checkout\Models\CartItem;
use Tipoff\Checkout\Tests\Support\Models\TestSellable;
use Tipoff\Checkout\Tests\TestCase;

class CartItemResourceTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function index()
    {
        TestSellable::createTable();
        $sellable = TestSellable::factory()->create();

        CartItem::factory()->count(4)->withSellable($sellable)->create();

        $this->actingAs(self::createPermissionedUser('view cart items', true));

        $response = $this->getJson('nova-api/cart-items')
            ->assertOk();

        $this->assertCount(4, $response->json('resources'));
    }

    /** @test */
    public function show()
    {
        TestSellable::createTable();
        $sellable = TestSellable::factory()->create();

        $cartItem = CartItem::factory()->withSellable($sellable)->create();

        $this->actingAs(User::factory()->create()->assignRole('Admin'));

        $response = $this->getJson("nova-api/cart-items/{$cartItem->id}")
            ->assertOk();

        $this->assertEquals($cartItem->id, $response->json('resource.id.value'));
    }
}
