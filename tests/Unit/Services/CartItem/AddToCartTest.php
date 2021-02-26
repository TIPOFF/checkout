<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Services\CartItem;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
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

class AddToCartTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithCarts;

    public function setUp(): void
    {
        parent::setUp();

        $this->setupCart(TestSellable::class);
    }

    /** @test */
    public function can_add_basic_item_to_cart()
    {
        Event::fake([
            CartItemCreated::class,
            CartItemUpdated::class,
        ]);

        /** @var CartItem $cartItem */
        $cartItem = Cart::createItem($this->sellable, 'item-id', 1234, 2);

        $cartItem = $this->cart->upsertItem($cartItem);
        $this->assertNotNull($cartItem->getId());

        $this->assertCount(1, $this->cart->getItems());

        Event::assertDispatched(CartItemCreated::class, 1);
        Event::assertNotDispatched(CartItemUpdated::class);
    }

    /** @test */
    public function can_add_linked_items_to_cart()
    {
        Event::fake([
            CartItemCreated::class,
            CartItemUpdated::class,
        ]);

        $parent = Cart::createItem($this->sellable, 'parent', 1010);
        $this->cart->upsertItem($parent);

        $child = Cart::createItem($this->sellable, 'item-id', 1234, 2)
            ->setParentItem($parent);
        $this->cart->upsertItem($child);

        $this->assertCount(2, $this->cart->getItems());

        Event::assertDispatched(CartItemCreated::class, 2);
        Event::assertNotDispatched(CartItemUpdated::class);
    }
}
