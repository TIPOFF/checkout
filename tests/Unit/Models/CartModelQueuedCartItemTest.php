<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Models;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Tipoff\Authorization\Models\EmailAddress;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Models\CartItem;
use Tipoff\Checkout\Tests\Support\Models\TestSellable;
use Tipoff\Checkout\Tests\TestCase;

class CartModelQueuedCartItemTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();

        TestSellable::createTable();
    }

    /** @test */
    public function item_is_queued()
    {
        $sellable = TestSellable::factory()->create();
        $cartItem = CartItem::factory()->withSellable($sellable)->make([
            'cart_id' => null,
        ]);

        Cart::queuedUpsertItem($cartItem);

        $this->assertNotNull(session()->get('checkout.queued_cart_item'));
    }

    /** @test */
    public function item_is_not_queued()
    {
        $emailAddress = EmailAddress::factory()->create();

        $sellable = TestSellable::factory()->create();
        $cartItem = CartItem::factory()->withSellable($sellable)->make([
            'cart_id' => null,
        ]);

        Cart::queuedUpsertItem($cartItem, $emailAddress->id);

        $this->assertNull(session()->get('checkout.queued_cart_item'));
    }

    /** @test */
    public function queued_item_is_dequeued()
    {
        $sellable = TestSellable::factory()->create();
        $cartItem = CartItem::factory()->withSellable($sellable)->make([
            'cart_id' => null,
        ]);

        Cart::queuedUpsertItem($cartItem);

        $emailAddress = EmailAddress::factory()->create();
        Auth::guard('email')->login($emailAddress);

        $this->assertNull(session()->get('checkout.queued_cart_item'));

        /** @var Cart $cart */
        $cart = Cart::activeCart($emailAddress->id);
        $this->assertEquals(1, $cart->cartItems->count());
    }
}
