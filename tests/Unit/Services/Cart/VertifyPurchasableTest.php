<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Services\Cart;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Tipoff\Checkout\Exceptions\CartNotValidException;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Models\CartItem;
use Tipoff\Checkout\Services\Cart\VerifyPurchasable;
use Tipoff\Checkout\Tests\Support\Models\TestSellable;
use Tipoff\Checkout\Tests\TestCase;
use Tipoff\Support\Events\Checkout\CartItemPurchaseVerification;

class VertifyPurchasableTest extends TestCase
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

        $handler = $this->app->make(VerifyPurchasable::class);
        $result = ($handler)($cart->refresh()->updatePricing());
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

        $handler = $this->app->make(VerifyPurchasable::class);
        $result = ($handler)($cart->refresh()->updatePricing());
        $this->assertEquals($result->id, $cart->id);

        Event::assertDispatched(CartItemPurchaseVerification::class, 1);
    }

    /** @test */
    public function cannot_be_empty()
    {
        /** @var Cart $cart */
        $cart = Cart::factory()->create();

        $this->expectException(CartNotValidException::class);

        $handler = $this->app->make(VerifyPurchasable::class);
        ($handler)($cart->refresh()->updatePricing());
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

        $handler = $this->app->make(VerifyPurchasable::class);
        ($handler)($cart->refresh()->updatePricing());
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

        $cart->refresh()->updatePricing();
        $cart->getItems()->first->setAmount(1234);

        $this->expectException(CartNotValidException::class);

        $handler = $this->app->make(VerifyPurchasable::class);
        ($handler)($cart);
    }
}
