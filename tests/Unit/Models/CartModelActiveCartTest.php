<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Models;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tipoff\Authorization\Models\User;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Models\CartItem;
use Tipoff\Checkout\Models\Order;
use Tipoff\Checkout\Tests\Support\Models\TestSellable;
use Tipoff\Checkout\Tests\TestCase;

class CartModelActiveCartTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();

        TestSellable::createTable();
    }

    /** @test */
    public function active_cart_none_exist()
    {
        $user = User::factory()->create();

        $cart = Cart::activeCart($user->id);
        $this->assertNotNull($cart);
        $this->assertEquals($user->id, $cart->user_id);
    }

    /** @test */
    public function active_cart_one_already_exist()
    {
        $user = User::factory()->create();

        $cart = Cart::activeCart($user->id);

        $newCart = Cart::activeCart($user->id);
        $this->assertEquals($cart->id, $newCart->id);
    }

    /** @test */
    public function active_cart_already_exist_with_expired_item()
    {
        $user = User::factory()->create();

        $cart = Cart::activeCart($user->id);

        // Active Item
        CartItem::factory()
            ->withSellable(TestSellable::factory()->create())
            ->create([
                'cart_id' => $cart,
                'expires_at' => Carbon::now()->addMonths(3),
            ]);

        $newCart = Cart::activeCart($user->id);
        $this->assertEquals($cart->id, $newCart->id);

        // Expired Item
        CartItem::factory()
            ->withSellable(TestSellable::factory()->create())
            ->create([
                'cart_id' => $cart,
                'expires_at' => Carbon::now()->subMinutes(3),
            ]);

        $newCart = Cart::activeCart($user->id);
        $this->assertNotEquals($cart->id, $newCart->id);
    }

    /** @test */
    public function active_cart_multiple_already_exist()
    {
        $user = User::factory()->create();
        Cart::factory()->create([
            'user_id' => $user,
        ]);
        $activeCart = Cart::factory()->create([
            'user_id' => $user,
        ]);

        $cart = Cart::factory()->create([
            'user_id' => $user,
        ]);
        CartItem::factory()
            ->withSellable(TestSellable::factory()->create())
            ->create([
                'cart_id' => $cart,
                'expires_at' => Carbon::now()->subMinutes(3),
            ]);

        $cart = Cart::activeCart($user->id);

        $this->assertEquals($activeCart->id, $cart->id);
    }

    /** @test */
    public function cart_with_order_conversion_not_active()
    {
        $user = User::factory()->create();
        $activeCart = Cart::factory()->create([
            'user_id' => $user,
        ]);
        Cart::factory()->create([
            'user_id' => $user,
            'order_id' => Order::factory()->create(),
        ]);

        $cart = Cart::activeCart($user->id);

        $this->assertEquals($activeCart->id, $cart->id);
    }
}
