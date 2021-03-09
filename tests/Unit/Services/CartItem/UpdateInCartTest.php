<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Services\CartItem;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Models\CartItem;
use Tipoff\Checkout\Tests\Support\Models\TestSellable;
use Tipoff\Checkout\Tests\Support\Traits\InteractsWithCarts;
use Tipoff\Checkout\Tests\TestCase;
use Tipoff\Support\Events\Checkout\CartItemCreated;
use Tipoff\Support\Events\Checkout\CartItemUpdated;

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
}
