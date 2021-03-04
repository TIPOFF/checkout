<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Services\Cart;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Services\Cart\ApplyCredits;
use Tipoff\Checkout\Tests\Support\Traits\InteractsWithCarts;
use Tipoff\Checkout\Tests\TestCase;
use Tipoff\Support\Contracts\Checkout\Vouchers\VoucherInterface;

class ApplyCreditsTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithCarts;

    /** @test */
    public function no_service()
    {
        /** @var Cart $cart */
        $cart = Cart::factory()->create([
            'credits' => 1234,
        ]);

        $service = $this->app->make(ApplyCredits::class);
        ($service)($cart);

        $this->assertEquals(1234, $cart->credits);
    }

    /** @test */
    public function with_service_credits_reset()
    {
        $voucherService = \Mockery::mock(VoucherInterface::class);
        $voucherService->shouldReceive('calculateAdjustments')
            ->once();
        $this->app->instance(VoucherInterface::class, $voucherService);

        /** @var Cart $cart */
        $cart = Cart::factory()->create([
            'credits' => 1234,
        ]);

        $service = $this->app->make(ApplyCredits::class);
        ($service)($cart);

        $this->assertEquals(0, $cart->credits);
    }

    /** @test */
    public function credits_are_capped()
    {
        $this->setupCart();

        $this->addCartItems([
            [500, 1],
        ]);

        $voucherService = \Mockery::mock(VoucherInterface::class);
        $voucherService->shouldReceive('calculateAdjustments')
            ->once()
            ->andReturnUsing(function (Cart $cart) {
                $cart->addCredits(1000);
            });
        $this->app->instance(VoucherInterface::class, $voucherService);

        $service = $this->app->make(ApplyCredits::class);
        ($service)($this->cart);

        $this->assertEquals(500, $this->cart->credits);
    }
}
