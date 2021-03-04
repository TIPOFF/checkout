<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Services\Cart;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Models\CartItem;
use Tipoff\Checkout\Services\Cart\ApplyCredits;
use Tipoff\Checkout\Services\Cart\ApplyDiscounts;
use Tipoff\Checkout\Tests\Support\Traits\InteractsWithCarts;
use Tipoff\Checkout\Tests\TestCase;
use Tipoff\Support\Contracts\Checkout\Discounts\DiscountInterface;
use Tipoff\Support\Contracts\Checkout\Vouchers\VoucherInterface;

class ApplyDiscountsTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithCarts;

    /** @test */
    public function no_service()
    {
        $this->setupCart();

        $this->addCartItems([
            [500, 1],
        ]);

        /** @var CartItem $item */
        $item = $this->cart->cartItems->first();
        $item->setAmountEach($item->getAmountEach()->addDiscounts(100))->save();

        $this->cart->discounts = 1234;
        $this->cart->save();

        $service = $this->app->make(ApplyDiscounts::class);
        ($service)($this->cart);

        $this->assertEquals(1234, $this->cart->discounts);
        $this->assertEquals(100, $this->cart->cartItems->first()->getAmountEach()->getDiscounts());
    }

    /** @test */
    public function with_service_discounts_reset()
    {
        $this->setupCart();

        $this->addCartItems([
            [500, 1],
        ]);

        /** @var CartItem $item */
        $item = $this->cart->cartItems->first();
        $item->setAmountEach($item->getAmountEach()->addDiscounts(100))->save();

        $this->cart->discounts = 1234;
        $this->cart->save();

        $this->assertEquals(1234, $this->cart->discounts);
        $this->assertEquals(100, $this->cart->cartItems->first()->getAmountEach()->getDiscounts());

        $discountService = \Mockery::mock(DiscountInterface::class);
        $discountService->shouldReceive('calculateAdjustments')
            ->once();
        $this->app->instance(DiscountInterface::class, $discountService);

        $service = $this->app->make(ApplyDiscounts::class);
        ($service)($this->cart);

        $this->assertEquals(0, $this->cart->discounts);
        $this->assertEquals(0, $this->cart->cartItems->first()->getAmountEach()->getDiscounts());
    }

    /** @test */
    public function discounts_are_capped()
    {
        $this->setupCart();

        $this->addCartItems([
            [500, 1],
        ]);

        $discountService = \Mockery::mock(DiscountInterface::class);
        $discountService->shouldReceive('calculateAdjustments')
            ->once()
            ->andReturnUsing(function (Cart $cart) {
                $cart->addDiscounts(1000);
            });
        $this->app->instance(DiscountInterface::class, $discountService);

        $service = $this->app->make(ApplyDiscounts::class);
        ($service)($this->cart);

        $this->assertEquals(500, $this->cart->discounts);
    }
}
