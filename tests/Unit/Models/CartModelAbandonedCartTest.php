<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Models;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Models\CartItem;
use Tipoff\Checkout\Tests\Support\Models\TestSellable;
use Tipoff\Checkout\Tests\TestCase;

class CartModelAbandonedCartTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();

        TestSellable::createTable();
    }

    /** @test */
    public function all_abandoned_carts()
    {
        $sellable = TestSellable::factory()->create();

        Cart::factory()
            ->count(4)
            ->create([
                'created_at' => Carbon::now(),
            ])->each(function (Cart $cart) use ($sellable) {
                // Expired Item
                CartItem::factory()
                    ->withSellable($sellable)
                    ->create([
                        'cart_id' => $cart,
                        'expires_at' => Carbon::now()->subMinutes(3),
                    ]);
            });

        Cart::factory()
            ->count(4)
            ->create([
                'created_at' => Carbon::now(),
            ])->each(function (Cart $cart) use ($sellable) {
                // Active Item
                CartItem::factory()
                    ->withSellable($sellable)
                    ->create([
                        'cart_id' => $cart,
                        'expires_at' => Carbon::now()->addDays(3),
                    ]);
            });

        $abandonedCarts = Cart::abandonedCarts();
        $this->assertCount(4, $abandonedCarts);
    }

    /** @test */
    public function abandoned_carts_with_date_filters()
    {
        $sellable = TestSellable::factory()->create();

        Cart::factory()
            ->count(2)
            ->create([
                'created_at' => Carbon::parse('2021-01-01 12:00:00'),
            ])->each(function (Cart $cart) use ($sellable) {
                // Expired Item
                CartItem::factory()
                    ->withSellable($sellable)
                    ->create([
                        'cart_id' => $cart,
                        'expires_at' => Carbon::now()->subMinutes(3),
                    ]);
            });

        Cart::factory()
            ->count(2)
            ->create([
                'created_at' => Carbon::parse('2021-02-01 12:00:00'),
            ])->each(function (Cart $cart) use ($sellable) {
                // Expired Item
                CartItem::factory()
                    ->withSellable($sellable)
                    ->create([
                        'cart_id' => $cart,
                        'expires_at' => Carbon::now()->subMinutes(3),
                    ]);
            });

        Cart::factory()
            ->count(2)
            ->create([
                'created_at' => Carbon::parse('2021-03-01 12:00:00'),
            ])->each(function (Cart $cart) use ($sellable) {
                // Expired Item
                CartItem::factory()
                    ->withSellable($sellable)
                    ->create([
                        'cart_id' => $cart,
                        'expires_at' => Carbon::now()->subMinutes(3),
                    ]);
            });

        $abandonedCarts = Cart::abandonedCarts('2021-03-01');
        $this->assertCount(2, $abandonedCarts);

        $abandonedCarts = Cart::abandonedCarts('2021-02-01');
        $this->assertCount(4, $abandonedCarts);

        $abandonedCarts = Cart::abandonedCarts('2021-01-01');
        $this->assertCount(6, $abandonedCarts);

        $abandonedCarts = Cart::abandonedCarts(null, '2021-04-01');
        $this->assertCount(6, $abandonedCarts);

        $abandonedCarts = Cart::abandonedCarts(null, '2021-03-01');
        $this->assertCount(4, $abandonedCarts);

        $abandonedCarts = Cart::abandonedCarts(null, '2021-02-01');
        $this->assertCount(2, $abandonedCarts);

        $abandonedCarts = Cart::abandonedCarts('2021-03-01', '2021-04-01');
        $this->assertCount(2, $abandonedCarts);

        $abandonedCarts = Cart::abandonedCarts('2021-02-01', '2021-03-01');
        $this->assertCount(2, $abandonedCarts);

        $abandonedCarts = Cart::abandonedCarts('2021-01-01', '2021-03-01');
        $this->assertCount(4, $abandonedCarts);
    }
}
