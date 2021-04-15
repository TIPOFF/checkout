<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Services\Cart;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Tipoff\Authorization\Models\EmailAddress;
use Tipoff\Authorization\Models\User;
use Tipoff\Checkout\Exceptions\CartNotValidException;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Models\CartItem;
use Tipoff\Checkout\Services\Cart\ApplyCredits;
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
        $emailAddress = EmailAddress::factory()->create([
            'user_id' => User::factory()->create(),
        ]);

        /** @var Cart $cart */
        $cart = Cart::factory()->create([
            'email_address_id' => $emailAddress,
        ]);

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

        $emailAddress = EmailAddress::factory()->create([
            'user_id' => User::factory()->create(),
        ]);

        /** @var Cart $cart */
        $cart = Cart::factory()->create([
            'email_address_id' => $emailAddress,
        ]);

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
    public function email_must_have_user()
    {
        $emailAddress = EmailAddress::factory()->create([
            'user_id' => null,
        ]);

        /** @var Cart $cart */
        $cart = Cart::factory()->create([
            'email_address_id' => $emailAddress,
        ]);

        // Active Item
        CartItem::factory()
            ->withSellable(TestSellable::factory()->create())
            ->active()
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
        $emailAddress = EmailAddress::factory()->create([
            'user_id' => User::factory()->create(),
        ]);

        /** @var Cart $cart */
        $cart = Cart::factory()->create([
            'email_address_id' => $emailAddress,
        ]);

        CartItem::factory()
            ->withSellable(TestSellable::factory()->create())
            ->active()
            ->create([
                'amount_each' => 1000,
                'cart_id' => $cart,
            ]);

        $cart->refresh()->updatePricing();

        $applyCredits = \Mockery::mock(ApplyCredits::class);
        $applyCredits->shouldReceive('__invoke')
            ->once()
            ->andReturnUsing(function (Cart $cart) {
                return $cart->addCredits(1234);
            });
        $this->app->instance(ApplyCredits::class, $applyCredits);

        $this->expectException(CartNotValidException::class);

        $handler = $this->app->make(VerifyPurchasable::class);
        ($handler)($cart);
    }

    /** @test */
    public function cannot_verify_items()
    {
        Event::fake([
            CartItemPurchaseVerification::class,
        ]);

        $emailAddress = EmailAddress::factory()->create([
            'user_id' => User::factory()->create(),
        ]);

        /** @var Cart $cart */
        $cart = Cart::factory()->create([
            'email_address_id' => $emailAddress,
        ]);

        $cartItem = CartItem::factory()
            ->withSellable(TestSellable::factory()->create())
            ->active()
            ->create([
                'cart_id' => $cart,
            ]);

        $cartItem->update(['sellable_id' => 123]);

        $this->expectException(CartNotValidException::class);

        $handler = $this->app->make(VerifyPurchasable::class);
        ($handler)($cart);
    }
}
