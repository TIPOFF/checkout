<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Models;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Models\CartItem;
use Tipoff\Checkout\Tests\Support\Models\TestSellable;
use Tipoff\Checkout\Tests\TestCase;
use Tipoff\Support\Events\Checkout\CartItemCreated;
use Tipoff\Support\Events\Checkout\CartItemRemoved;
use Tipoff\Support\Events\Checkout\CartItemUpdated;

class CartModelInterfaceTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();

        TestSellable::createTable();
    }

    /** @test */
    public function can_create_basic_item()
    {
        Event::fake([
            CartItemCreated::class,
            CartItemUpdated::class,
        ]);

        $sellable = TestSellable::factory()->create();

        /** @var CartItem $cartItem */
        $cartItem = Cart::createItem($sellable, 'item-id', 1234, 2);

        $this->assertEquals(2, $cartItem->getQuantity());
        $this->assertEquals(1234, $cartItem->getAmount()->getOriginalAmount());
        $this->assertEquals(0, $cartItem->getAmount()->getDiscounts());
        $this->assertEquals('item-id', $cartItem->getItemId());
        $this->assertFalse($cartItem->isExpired());
        $this->assertEquals($sellable->getMorphClass(), $cartItem->getSellable()->getMorphClass());

        $this->assertNull($cartItem->getCart());
        $this->assertNull($cartItem->getTaxCode());
        $this->assertNull($cartItem->getLocationId());
        $this->assertNull($cartItem->getParentItem());
        $this->assertNull($cartItem->getRootItem());

        Event::assertNotDispatched(CartItemCreated::class);
        Event::assertNotDispatched(CartItemUpdated::class);
    }

    /** @test */
    public function can_modify_basic_item()
    {
        Event::fake([
            CartItemCreated::class,
            CartItemUpdated::class,
        ]);

        /** @var Cart $cart */
        $cart = Cart::factory()->create();

        $parent = Cart::createItem(TestSellable::factory()->create(), 'parent', 1010);
        $parent = $cart->upsertItem($parent);

        /** @var CartItem $cartItem */
        $cartItem = Cart::createItem(TestSellable::factory()->create(), 'item-id', 1234, 2)
            ->setLocationId(321)
            ->setTaxCode('ABC')
            ->setParentItem($parent);

        $this->assertEquals('ABC', $cartItem->getTaxCode());
        $this->assertEquals(321, $cartItem->getLocationId());
        $this->assertEquals($parent, $cartItem->getParentItem());
        $this->assertEquals($parent, $cartItem->getRootItem());

        Event::assertDispatched(CartItemCreated::class, 1);
        Event::assertNotDispatched(CartItemUpdated::class);
    }

    /** @test */
    public function can_remove_linked_parent_from_cart()
    {
        // Allow DB events to fire
        Event::fake([
            CartItemRemoved::class,
        ]);

        /** @var Cart $cart */
        $cart = Cart::factory()->create();

        $sellableParent = TestSellable::factory()->create();
        $sellableChild = TestSellable::factory()->create();

        $parent = Cart::createItem($sellableParent, 'parent', 1010);
        $parent = $cart->upsertItem($parent);

        $child = Cart::createItem($sellableChild, 'item-id', 1234, 2)
            ->setParentItem($parent);
        $cart->upsertItem($child);

        $this->assertCount(2, $cart->getItems());

        $cart->removeItem($sellableParent, 'parent');

        $this->assertCount(0, $cart->getItems());

        Event::assertDispatched(CartItemRemoved::class, 2);
    }

    /** @test */
    public function can_remove_linked_child_from_cart()
    {
        // Allow DB events to fire
        Event::fake([
            CartItemRemoved::class,
        ]);

        /** @var Cart $cart */
        $cart = Cart::factory()->create();

        $sellableParent = TestSellable::factory()->create();
        $sellableChild = TestSellable::factory()->create();

        $parent = Cart::createItem($sellableParent, 'parent', 1010);
        $parent = $cart->upsertItem($parent);

        $child = Cart::createItem($sellableChild, 'item-id', 1234, 2)
            ->setParentItem($parent);
        $cart->upsertItem($child);

        $this->assertCount(2, $cart->getItems());

        $cart->removeItem($sellableChild, 'item-id');

        $this->assertCount(1, $cart->getItems());

        Event::assertDispatched(CartItemRemoved::class, 1);
    }

    /** @test */
    public function can_delete_cart_with_linked_items()
    {
        // Allow DB events to fire
        Event::fake([
            CartItemRemoved::class,
        ]);

        /** @var Cart $cart */
        $cart = Cart::factory()->create();

        $sellableParent = TestSellable::factory()->create();
        $sellableChild = TestSellable::factory()->create();

        $parent = Cart::createItem($sellableParent, 'parent', 1010);
        $parent = $cart->upsertItem($parent);

        $child = Cart::createItem($sellableChild, 'item-id', 1234, 2)
            ->setParentItem($parent);
        $cart->upsertItem($child);

        $cart->delete();

        Event::assertDispatched(CartItemRemoved::class, 2);
    }

    /** @test */
    public function remove_is_scoped_by_cart()
    {
        // Allow DB events to fire
        Event::fake([
            CartItemRemoved::class,
        ]);

        /** @var Cart $cart1 */
        $cart1 = Cart::factory()->create();

        /** @var Cart $cart2 */
        $cart2 = Cart::factory()->create();

        $sellable = TestSellable::factory()->create();

        $item1 = Cart::createItem($sellable, 'item', 1010);
        $item1 = $cart1->upsertItem($item1);

        $item2 = Cart::createItem($sellable, 'item', 1010);
        $item2 = $cart2->upsertItem($item2);

        $this->assertCount(1, $cart1->getItems());
        $this->assertCount(1, $cart2->getItems());

        $cart1->removeItem($sellable, 'item');
        $this->assertCount(0, $cart1->getItems());
        $this->assertCount(1, $cart2->getItems());

        $cart2->removeItem($sellable, 'item');
        $this->assertCount(0, $cart1->getItems());
        $this->assertCount(0, $cart2->getItems());

        Event::assertDispatched(CartItemRemoved::class, 2);
    }

    /** @test */
    public function remove_ignores_items_not_found()
    {
        /** @var Cart $cart1 */
        $cart1 = Cart::factory()->create();

        $sellable = TestSellable::factory()->create();

        $item1 = Cart::createItem($sellable, 'item', 1010);
        $cart1->upsertItem($item1);

        $this->assertCount(1, $cart1->getItems());

        $cart1->removeItem($sellable, 'item1');
        $this->assertCount(1, $cart1->getItems());

        $cart1->removeItem($sellable, 'item');
        $this->assertCount(0, $cart1->getItems());
    }

    /** @test */
    public function find_is_scoped_by_cart()
    {
        /** @var Cart $cart1 */
        $cart1 = Cart::factory()->create();

        /** @var Cart $cart2 */
        $cart2 = Cart::factory()->create();

        $sellable = TestSellable::factory()->create();

        $item1 = Cart::createItem($sellable, 'item1', 1010);
        $cart1->upsertItem($item1);

        $item2 = Cart::createItem($sellable, 'item2', 1010);
        $cart2->upsertItem($item2);

        $item = $cart1->findItem($sellable, 'item1');
        $this->assertNotNull($item);
        $item = $cart2->findItem($sellable, 'item1');
        $this->assertNull($item);

        $item = $cart1->findItem($sellable, 'item2');
        $this->assertNull($item);
        $item = $cart2->findItem($sellable, 'item2');
        $this->assertNotNull($item);
    }

    /** @test */
    public function find_ignores_items_not_found()
    {
        /** @var Cart $cart1 */
        $cart1 = Cart::factory()->create();

        $sellable = TestSellable::factory()->create();

        $item1 = Cart::createItem($sellable, 'item', 1010);
        $cart1->upsertItem($item1);

        $this->assertCount(1, $cart1->getItems());

        $item = $cart1->findItem($sellable, 'item1');
        $this->assertNull($item);

        $item = $cart1->findItem($sellable, 'item');
        $this->assertEquals($item1->getId(), $item->getId());
    }
}
