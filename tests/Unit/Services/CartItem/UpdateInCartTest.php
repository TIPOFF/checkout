<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Services\CartItem;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Tipoff\Checkout\Exceptions\CartNotValidException;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Models\CartItem;
use Tipoff\Checkout\Services\Cart\CompletePurchase;
use Tipoff\Checkout\Tests\Support\Models\TestSellable;
use Tipoff\Checkout\Tests\Support\Traits\InteractsWithCarts;
use Tipoff\Checkout\Tests\TestCase;
use Tipoff\Support\Events\Checkout\CartItemCreated;
use Tipoff\Support\Events\Checkout\CartItemUpdated;
use Tipoff\Support\Events\Checkout\OrderCreated;
use Tipoff\Support\Events\Checkout\OrderItemCreated;
use Tipoff\TestSupport\Models\User;

class UpdateInCartTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithCarts;

    public function setUp(): void
    {
        parent::setUp();

        $this->setupCart(TestSellable::class);
    }

    /** @test */
    public function can_upsert_existing_cart_item()
    {
        Event::fake([
            CartItemCreated::class,
            CartItemUpdated::class,
        ]);

        /** @var CartItem $cartItem */
        $cartItem = Cart::createItem($this->sellable, 'item-id', 1234, 2);
        $cartItem = $this->cart->upsertItem($cartItem);

        $cartItem->setTaxCode('ABCD');
        $this->cart->upsertItem($cartItem);

        Event::assertDispatched(CartItemCreated::class, 1);
        Event::assertDispatched(CartItemUpdated::class, 1);
    }

    /** @test */
    public function cannot_upsert_existing_item_as_new()
    {
        /** @var CartItem $cartItem */
        $cartItem = Cart::createItem($this->sellable, 'item-id', 1234, 2);
        $this->cart->upsertItem($cartItem);
        $this->assertCount(1, $this->cart->getItems());

        $this->expectException(CartNotValidException::class);

        $cartItem = Cart::createItem($this->sellable, 'item-id', 5678, 2);
        $this->cart->upsertItem($cartItem);
    }
}
