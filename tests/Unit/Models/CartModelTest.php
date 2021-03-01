<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Models;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Models\CartItem;
use Tipoff\Checkout\Tests\Support\Models\TestSellable;
use Tipoff\Checkout\Tests\TestCase;

class CartModelTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();

        TestSellable::createTable();
    }

    /** @test */
    public function create()
    {
        $cart = Cart::factory()->create();
        $this->assertNotNull($cart);
    }

    /** @test */
    public function get_cart_items()
    {
        /** @var Cart $cart */
        $cart = Cart::factory()->create();

        $cartItems = $cart->getItems();
        $this->assertCount(0, $cartItems);

        CartItem::factory()
            ->count(3)
            ->withSellable(TestSellable::factory()->create())
            ->create([
                'cart_id' => $cart,
            ]);

        $cart->refresh();

        $cartItems = $cart->getItems();
        $this->assertCount(3, $cartItems);
    }
}
