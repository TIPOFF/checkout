<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Models;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Tipoff\Checkout\Exceptions\CartNotValidException;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Models\CartItem;
use Tipoff\Checkout\Tests\Support\Models\TestSellable;
use Tipoff\Checkout\Tests\TestCase;
use Tipoff\Support\Events\Checkout\CartItemPurchaseVerification;

class CartModelPurchaseTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();

        TestSellable::createTable();
    }

    /** @test */
    public function valid_cart_is_purchasable()
    {
        /** @var Cart $cart */
        $cart = Cart::factory()->create();

        // Active Item
        CartItem::factory()
            ->withSellable(TestSellable::factory()->create())
            ->active()
            ->create([
                'cart_id' => $cart,
            ]);

        $result = $cart->updatePricing()->verifyPurchasable();
        $this->assertEquals($result->id, $cart->id);
    }

    /** @test */
    public function cart_items_are_verified()
    {
        Event::fake([
            CartItemPurchaseVerification::class,
        ]);

        /** @var Cart $cart */
        $cart = Cart::factory()->create();

        // Active Item
        CartItem::factory()
            ->withSellable(TestSellable::factory()->create())
            ->active()
            ->create([
                'cart_id' => $cart,
            ]);

        $result = $cart->updatePricing()->verifyPurchasable();
        $this->assertEquals($result->id, $cart->id);

        Event::assertDispatched(CartItemPurchaseVerification::class, 1);
    }

    /** @test */
    public function cannot_be_empty()
    {
        /** @var Cart $cart */
        $cart = Cart::factory()->create();

        $this->expectException(CartNotValidException::class);
        $cart->verifyPurchasable();
    }

    /** @test */
    public function cannot_contain_expired_item()
    {
        /** @var Cart $cart */
        $cart = Cart::factory()->create();

        CartItem::factory()
            ->withSellable(TestSellable::factory()->create())
            ->active()
            ->create([
                'cart_id' => $cart,
            ]);

        CartItem::factory()
            ->withSellable(TestSellable::factory()->create())
            ->active(false)
            ->create([
                'cart_id' => $cart,
            ]);

        $this->expectException(CartNotValidException::class);
        $cart->verifyPurchasable();
    }

    /** @test */
    public function change_in_pricing_detected()
    {
        /** @var Cart $cart */
        $cart = Cart::factory()->create();

        CartItem::factory()
            ->withSellable(TestSellable::factory()->create())
            ->active()
            ->create([
                'amount' => 1000,
                'cart_id' => $cart,
            ]);

        $cart->updatePricing();
        $cart->getCartItems()->first->setAmount(1234);

        $this->expectException(CartNotValidException::class);
        $cart->verifyPurchasable();
    }
}
