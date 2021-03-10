<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Services\Order;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tipoff\Addresses\Models\Zip;
use Tipoff\Authorization\Models\User;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Models\CartItem;
use Tipoff\Checkout\Models\OrderItem;
use Tipoff\Checkout\Services\Order\CreateFromCart;
use Tipoff\Checkout\Tests\Support\Models\TestSellable;
use Tipoff\Checkout\Tests\TestCase;
use Tipoff\Support\Objects\DiscountableValue;

class CreateFromCartTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();

        TestSellable::createTable();
    }

    /** @test */
    public function create_from_empty_cart()
    {
        $user = User::factory()->create();
        $cart = Cart::factory()->create([
            'shipping' => new DiscountableValue(123),
            'discounts' => 456,
            'user_id' => $user,
            'location_id' => 321,
        ])->refresh();

        $this->actingAs($user);

        $handler = $this->app->make(CreateFromCart::class);
        $order = ($handler)($cart);

        $this->assertEquals(123, $order->getShipping()->getOriginalAmount());
        $this->assertEquals(456, $order->getDiscounts());
        $this->assertEquals($user->id, $order->user_id);
        $this->assertEquals(321, $order->location_id);
        $this->assertEquals($user->id, $order->creator_id);
        $this->assertEquals($user->id, $order->updater_id);
    }

    /** @test */
    public function create_from_single_item_cart()
    {
        $user = User::factory()->create();

        /** @var Cart $cart */
        $cart = Cart::factory()->create([
            'shipping' => 123,
        ]);

        $sellable = TestSellable::factory()->create();

        /** @var CartItem $cartItem */
        $cartItem = Cart::createItem($sellable, 'item-id', 1234, 2)
            ->setTaxCode('ABC')
            ->setLocationId(456)
            ->setMetaData('dot.value', 'DotValue');

        $cart->upsertItem($cartItem);

        $this->actingAs($user);

        $handler = $this->app->make(CreateFromCart::class);
        $order = ($handler)($cart);

        // Validate container
        $this->assertEquals(123, $order->getShipping()->getOriginalAmount());
        $this->assertEquals($user->id, $order->user_id);
        $this->assertEquals(456, $order->getLocationId());
        $this->assertEquals($user->id, $order->creator_id);
        $this->assertEquals($user->id, $order->updater_id);
        $this->assertCount(1, $order->getItems());

        // Validate item
        /** @var OrderItem $orderItem */
        $orderItem = $order->getItems()->first();
        $this->assertEquals('item-id', $orderItem->getItemId());
        $this->assertEquals(1234, $orderItem->getAmountEach()->getOriginalAmount());
        $this->assertEquals(2468, $orderItem->getAmountTotal()->getOriginalAmount());
        $this->assertEquals(2, $orderItem->getQuantity());
        $this->assertEquals('ABC', $orderItem->getTaxCode());
        $this->assertEquals(456, $orderItem->getLocationId());
        $this->assertEquals($user->id, $orderItem->creator_id);
        $this->assertEquals($user->id, $orderItem->updater_id);
    }

    /** @test */
    public function create_from_linked_items_cart()
    {
        $user = User::factory()->create();

        /** @var Cart $cart */
        $cart = Cart::factory()->create();

        $sellable = TestSellable::factory()->create();

        /** @var CartItem $parent */
        $parent = Cart::createItem($sellable, 'parent', 1000, 1);
        $parent = $cart->upsertItem($parent);

        $child = Cart::createItem($sellable, 'child', 200, 1)
            ->setParentItem($parent);
        $cart->upsertItem($child);

        $cart->upsertItem(Cart::createItem($sellable, 'root', 0, 1));

        $this->assertCount(3, $cart->getItems());

        $this->actingAs($user);

        $handler = $this->app->make(CreateFromCart::class);
        $order = ($handler)($cart);

        // Validate container
        $this->assertCount(3, $order->getItems());
        $this->assertEquals(1200, $order->getItemAmountTotal()->getOriginalAmount());

        // Validate items
        $orderItem = $order->findItem($sellable, 'parent');
        $this->assertEquals('parent', $orderItem->getItemId());
        $this->assertEquals(1000, $orderItem->getAmountTotal()->getOriginalAmount());

        $orderItem = $order->findItem($sellable, 'child');
        $this->assertEquals('child', $orderItem->getItemId());
        $this->assertEquals(200, $orderItem->getAmountTotal()->getOriginalAmount());
    }

    /** @test */
    public function addresses_are_copied()
    {
        $user = User::factory()->create();
        /** @var Cart $cart */
        $cart = Cart::factory()->create([
            'shipping' => new DiscountableValue(123),
            'discounts' => 456,
            'user_id' => $user,
            'location_id' => 321,
        ])->refresh();

        $this->actingAs($user);

        $zip = Zip::factory()->create();
        $cart->setBillingAddress(Cart::createDomesticAddress('billing', null, 'Boston', $zip));
        $cart->setShippingAddress(Cart::createDomesticAddress('shipping', null, 'Boston', $zip));

        $handler = $this->app->make(CreateFromCart::class);
        $order = ($handler)($cart);

        $address = $order->getBillingAddress();
        $this->assertNotNull($address);
        $this->assertEquals('billing', $address->domesticAddress->address_line_1);

        $address = $order->getShippingAddress();
        $this->assertNotNull($address);
        $this->assertEquals('shipping', $address->domesticAddress->address_line_1);
    }
}
