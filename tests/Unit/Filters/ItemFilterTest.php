<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Filters;


use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tipoff\Authorization\Models\User;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Models\CartItem;
use Tipoff\Checkout\Models\OrderItem;
use Tipoff\Checkout\Tests\Support\Models\TestSellable;
use Tipoff\Checkout\Tests\TestCase;
use Tipoff\Locations\Models\Location;

class ItemFilterTest extends TestCase
{
    use DatabaseTransactions;

    /** @test  */
    public function filter_by_type()
    {
        TestSellable::createTable();

        $sellable = TestSellable::factory()->create();

        /** @var Cart $cart */
        $cart = Cart::factory()->create();
        $cart->upsertItem(
            Cart::createItem($sellable, 'item-1', 1234, 2)
        );

        $cart = Cart::factory()->create();
        $cart->upsertItem(
            Cart::createItem($sellable, 'item-2', 1234, 2)
        );

        $items = Cart::itemFilter()
            ->bySellableType(TestSellable::class)
            ->apply();

        $this->assertCount(2, $items);

        $items = Cart::itemFilter()
            ->bySellableType(User::class)
            ->apply();

        $this->assertCount(0, $items);
    }

    /** @test  */
    public function filter_by_type_with_children()
    {
        TestSellable::createTable();
        TestFee::createTable();

        $sellable = TestSellable::factory()->create();
        $fee = TestFee::factory()->create();

        /** @var Cart $cart */
        $cart = Cart::factory()->create();
        $cartItem = $cart->upsertItem(
            Cart::createItem($sellable, 'item-1', 1234, 2)
        );

        $cart->upsertItem(
            Cart::createItem($fee, 'item-fee', 1234, 2)
                ->setParentItem($cartItem)
        );

        $items = Cart::itemFilter()
            ->bySellableType(TestSellable::class)
            ->apply();

        $this->assertCount(2, $items);

        $items = Cart::itemFilter()
            ->bySellableType(TestSellable::class, false)
            ->apply();

        $this->assertCount(1, $items);

        $items = Cart::itemFilter()
            ->bySellableType(TestFee::class)
            ->apply();

        $this->assertCount(1, $items);
    }

    /** @test  */
    public function filter_by_location()
    {
        TestSellable::createTable();

        $sellable = TestSellable::factory()->create();
        $location = Location::factory()->create();

        /** @var Cart $cart */
        $cart = Cart::factory()->create();
        $cart->upsertItem(
            Cart::createItem($sellable, 'item-1', 1234, 2)
                ->setLocationId($location->id)
        );

        $cart = Cart::factory()->create();
        $cart->upsertItem(
            Cart::createItem($sellable, 'item-2', 1234, 2)
                ->setLocationId(21)
        );

        $items = Cart::itemFilter()
            ->byLocation($location)
            ->apply();

        $this->assertCount(1, $items);

        $items = Cart::itemFilter()
            ->byLocation(21)
            ->apply();

        $this->assertCount(1, $items);

        $items = Cart::itemFilter()
            ->byLocation(22)
            ->apply();

        $this->assertCount(0, $items);
    }

    /** @test  */
    public function filter_by_dates()
    {
        TestSellable::createTable();

        $sellable = TestSellable::factory()->create();

        /** @var Cart $cart */
        $cart = Cart::factory()->create();

        try {
            Carbon::setTestNow('2020-01-01 12:00:00');
            $cart->upsertItem(
                Cart::createItem($sellable, 'item-1', 1234, 2)
            );

            Carbon::setTestNow('2020-02-01 12:00:00');
            $cart->upsertItem(
                Cart::createItem($sellable, 'item-2', 1234, 2)
            );

            Carbon::setTestNow('2020-03-01 12:00:00');
            $cart->upsertItem(
                Cart::createItem($sellable, 'item-3', 1234, 2)
            );

            Carbon::setTestNow('2020-04-01 12:00:00');
            $cart->upsertItem(
                Cart::createItem($sellable, 'item-4', 1234, 2)
            );

            $items = Cart::itemFilter()
                ->byStartDate('2020-02-15')
                ->apply();
            $this->assertCount(2, $items);

            $items = Cart::itemFilter()
                ->byStartDate('2020-04-15')
                ->apply();

            $this->assertCount(0, $items);

            $items = Cart::itemFilter()
                ->byEndDate('2020-03-15')
                ->apply();

            $this->assertCount(3, $items);

            $items = Cart::itemFilter()
                ->byEndDate('2020-01-15')
                ->apply();

            $this->assertCount(1, $items);

            $items = Cart::itemFilter()
                ->byStartDate('2020-02-15')
                ->byEndDate('2020-03-15')
                ->apply();

            $this->assertCount(1, $items);

            Carbon::setTestNow('2020-04-02 12:00:00');

            $items = Cart::itemFilter()
                ->yesterday()
                ->apply();

            $this->assertCount(1, $items);

            $cart->upsertItem(
                Cart::createItem($sellable, 'item-5', 1234, 2)
            );

            Carbon::setTestNow('2020-04-03 12:00:00');

            $items = Cart::itemFilter()
                ->week()
                ->apply();

            $this->assertCount(2, $items);

            $items = Cart::itemFilter()
                ->weekComparison()
                ->apply();

            $this->assertCount(0, $items);

            Carbon::setTestNow('2020-04-10 12:00:00');

            $items = Cart::itemFilter()
                ->weekComparison()
                ->apply();

            $this->assertCount(2, $items);

        } finally {
            Carbon::setTestNow(null);
        }
    }
}

class TestFee extends TestSellable
{
}
