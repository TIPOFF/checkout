<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Services\Cart;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Tipoff\Checkout\Exceptions\CartNotValidException;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Models\CartItem;
use Tipoff\Checkout\Models\Order;
use Tipoff\Checkout\Models\OrderItem;
use Tipoff\Checkout\Services\Cart\CompletePurchase;
use Tipoff\Checkout\Services\Cart\VerifyPurchasable;
use Tipoff\Checkout\Services\Order\CreateFromCart;
use Tipoff\Checkout\Tests\Support\Models\TestSellable;
use Tipoff\Checkout\Tests\TestCase;
use Tipoff\Support\Events\Checkout\CartItemPurchaseVerification;
use Tipoff\Support\Events\Checkout\OrderCreated;
use Tipoff\Support\Events\Checkout\OrderItemCreated;
use Tipoff\Support\Objects\DiscountableValue;
use Tipoff\TestSupport\Models\User;

class CompletePurchaseTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();

        TestSellable::createTable();
    }

    /** @test */
    public function purchase_single_item_cart()
    {
        Event::fake([
            OrderItemCreated::class,
            OrderCreated::class,
        ]);

        $user = User::factory()->create();

        /** @var Cart $cart */
        $cart = Cart::factory()->create();

        $sellable = TestSellable::factory()->create();

        $cart->upsertItem(
            Cart::createItem($sellable, 'item-id', 1234, 2)
        );

        $this->actingAs($user);

        $handler = $this->app->make(CompletePurchase::class);
        $order = ($handler)($cart);

        $cart->refresh();
        $this->assertNotNull($cart->deleted_at);
        $this->assertEquals($cart->order_id, $order->id);;

        Event::assertDispatched(OrderItemCreated::class, 1);
        Event::assertDispatched(OrderCreated::class, 1);
    }

    /** @test */
    public function purchase_linked_items_cart()
    {
        Event::fake([
            OrderItemCreated::class,
            OrderCreated::class,
        ]);

        $user = User::factory()->create();

        /** @var Cart $cart */
        $cart = Cart::factory()->create();

        $sellable = TestSellable::factory()->create();

        /** @var CartItem $parent */
        $parent = $cart->upsertItem(
            Cart::createItem($sellable, 'parent', 1000, 1)
        );

        $child = Cart::createItem($sellable, 'child', 200, 1)
            ->setParentItem($parent);
        $cart->upsertItem($child);

        $cart->upsertItem(Cart::createItem($sellable, 'root', 0, 1));

        $this->actingAs($user);

        $handler = $this->app->make(CompletePurchase::class);
        ($handler)($cart);

        Event::assertDispatched(OrderItemCreated::class, 3);
        Event::assertDispatched(OrderCreated::class, 1);
    }
}
