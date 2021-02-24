<?php

declare(strict_types=1);

namespace Tipoff\Discounts\Tests\Feature\Checkout;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Models\CartItem;
use Tipoff\Checkout\Models\Order;
use Tipoff\Checkout\Tests\Support\Models\TestSellable;
use Tipoff\Checkout\Tests\TestCase;
use Tipoff\TestSupport\Models\User;

class CheckoutTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();

        TestSellable::createTable();
    }

    /** @test */
    public function add_sellable_to_cart()
    {
        $this->actingAs(User::factory()->create());

        /** @var TestSellable $sellable */
        $sellable = TestSellable::factory()->create();

        $sellable->addToCart(4);

        $this->assertDatabaseCount('carts', 1);
        $this->assertDatabaseCount('cart_items', 1);

        /** @var CartItem $cartItem */
        $cartItem = CartItem::all()->first();
        $this->assertEquals($cartItem->sellable_id, $sellable->id);
        $this->assertEquals($cartItem->sellable_type, $sellable->getMorphClass());
        $this->assertEquals(4, $cartItem->getQuantity());

        /** @var Cart $cart */
        $cart = Cart::all()->first();
        $this->assertEquals(4000, $cart->getBalanceDue());
    }

    /** @test */
    public function update_sellable_in_cart()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        /** @var TestSellable $sellable */
        $sellable = TestSellable::factory()->create();
        $sellable->addToCart(4);

        $sellable->upsertToCart(5);

        $this->assertDatabaseCount('carts', 1);
        $this->assertDatabaseCount('cart_items', 1);

        /** @var CartItem $cartItem */
        $cartItem = CartItem::all()->first();
        $this->assertEquals($cartItem->sellable_id, $sellable->id);
        $this->assertEquals($cartItem->sellable_type, $sellable->getMorphClass());
        $this->assertEquals(5, $cartItem->getQuantity());

        /** @var Cart $cart */
        $cart = Cart::all()->first();
        $this->assertEquals(5000, $cart->getBalanceDue());
    }

    /** @test */
    public function add_item_with_fee_to_cart()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        /** @var TestSellable $sellable */
        $sellable = TestSellable::factory()->create();
        $sellable->upsertToCartWithFee(4, 5000);

        $this->assertDatabaseCount('carts', 1);
        $this->assertDatabaseCount('cart_items', 2);

        /** @var Cart $cart */
        $cart = Cart::all()->first();
        $this->assertEquals(9000, $cart->getBalanceDue());
    }
}
