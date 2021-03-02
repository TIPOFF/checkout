<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Feature\Checkout;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tipoff\Authorization\Models\User;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Tests\Support\Models\TestSellable;
use Tipoff\Checkout\Tests\TestCase;
use Tipoff\Support\Events\Checkout\CartItemCreated;
use Tipoff\Support\Events\Checkout\CartItemPurchaseVerification;
use Tipoff\Support\Events\Checkout\OrderCreated;
use Tipoff\Support\Events\Checkout\OrderItemCreated;

class CheckoutTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();

        TestSellable::createTable();
    }

    /** @test */
    public function checkout_with_payment_simulation()
    {
        Event::fake([
            CartItemCreated::class,
            CartItemPurchaseVerification::class,
            OrderItemCreated::class,
            OrderCreated::class,
        ]);

        $user = User::factory()->create();
        $this->actingAs($user);

        /** @var TestSellable $sellable */
        $sellable = TestSellable::factory()->create();
        $sellable->upsertToCartWithFee(4, 5000);

        // APP LEVEL CHECKOUT SIMULATION
        // - final verification
        // - capture payment
        // - create order from cart
        // - associate payment with order
        DB::transaction(function () use ($user) {
            $cart = Cart::activeCart($user->id);

            // Final check all is good to go
            $cart->verifyPurchasable();

            // Authorize Payment for balance due
            if ($ccAmountDue = $cart->getBalanceDue()) {
                $this->assertGreaterThan(0, $ccAmountDue);
                // PAYMENT RESPONSIBILITY
                // $payment = fn($user, $cart->getLocationId(), $ccAmountDue);
            }

            // With payment confirmed, complete purchase
            $order = $cart->completePurchase();
            $this->assertNotNull($order);

            // PAYMENT RESPONSIBILITY
            // Associate payment with order
            // $payment->order()->associate($order);
            // $payment->save();
            // new Transaction()??
        });

        Event::assertDispatched(CartItemCreated::class, 2);
        Event::assertDispatched(CartItemPurchaseVerification::class, 2);
        Event::assertDispatched(OrderItemCreated::class, 2);
        Event::assertDispatched(OrderCreated::class, 1);
    }

    /** @test */
    public function checkout_without_payment_simulation()
    {
        Event::fake([
            CartItemCreated::class,
            CartItemPurchaseVerification::class,
            OrderItemCreated::class,
            OrderCreated::class,
        ]);

        $user = User::factory()->create();
        $this->actingAs($user);

        /** @var TestSellable $sellable */
        $sellable = TestSellable::factory()->create();
        $sellable->upsertToCartWithFee(4, 5000);

        // APP LEVEL CHECKOUT SIMULATION
        // - final verification
        // - create order from cart
        DB::transaction(function () use ($user) {
            $cart = Cart::activeCart($user->id);

            // Final check all is good to go
            $cart->verifyPurchasable();

            // With payment confirmed, complete purchase
            $order = $cart->completePurchase();
            $this->assertNotNull($order);
        });

        Event::assertDispatched(CartItemCreated::class, 2);
        Event::assertDispatched(CartItemPurchaseVerification::class, 2);
        Event::assertDispatched(OrderItemCreated::class, 2);
        Event::assertDispatched(OrderCreated::class, 1);
    }
}
