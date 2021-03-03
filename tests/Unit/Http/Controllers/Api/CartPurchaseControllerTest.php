<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Http\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tipoff\Authorization\Models\User;
use Tipoff\Checkout\Models\CartItem;
use Tipoff\Checkout\Tests\Support\Models\TestSellable;
use Tipoff\Checkout\Tests\TestCase;

class CartPurchaseControllerTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function cannot_purchase_empty_cart()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this
            ->postJson('tipoff/cart/purchase')
            ->assertStatus(422);

        $this->assertEquals('Cart is empty.', $response->json('errors.cart.0'));
    }

    /** @test */
    public function cannot_purchase_expired_item()
    {
        TestSellable::createTable();

        $user = User::factory()->create();

        $this->actingAs($user);

        /** @var TestSellable $sellable */
        $sellable = TestSellable::factory()->create();
        $sellable->addToCart(1);

        $sellable = TestSellable::factory()->create();

        /** @var CartItem $item */
        $item = $sellable->addToCart(1);
        $item->expires_at = Carbon::now()->subDays(1);
        $item->save();

        $response = $this
            ->postJson('tipoff/cart/purchase')
            ->assertStatus(422);

        $this->assertEquals('Cart is empty.', $response->json('errors.cart.0'));
    }
}
