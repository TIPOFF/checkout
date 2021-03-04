<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Services\Cart;

use Tipoff\Checkout\Services\Cart\ActiveAdjustments;
use Tipoff\Checkout\Tests\TestCase;
use Tipoff\Support\Contracts\Checkout\Discounts\DiscountInterface;
use Tipoff\Support\Contracts\Checkout\Vouchers\VoucherInterface;

class ActiveAdjustmentsTest extends TestCase
{
    /** @test */
    public function no_services()
    {
        $services = (new ActiveAdjustments())();
        $this->assertCount(0, $services);
    }

    /** @test */
    public function discount_service()
    {
        $discountService = \Mockery::mock(DiscountInterface::class);
        $this->app->instance(DiscountInterface::class, $discountService);

        $services = (new ActiveAdjustments())();
        $this->assertCount(1, $services);
        $this->assertInstanceOf(DiscountInterface::class, $services->first());
    }

    /** @test */
    public function voucher_service()
    {
        $voucherService = \Mockery::mock(VoucherInterface::class);
        $this->app->instance(VoucherInterface::class, $voucherService);

        $services = (new ActiveAdjustments())();
        $this->assertCount(1, $services);
        $this->assertInstanceOf(VoucherInterface::class, $services->first());
    }

    /** @test */
    public function multiple_services()
    {
        $discountService = \Mockery::mock(DiscountInterface::class);
        $this->app->instance(DiscountInterface::class, $discountService);

        $voucherService = \Mockery::mock(VoucherInterface::class);
        $this->app->instance(VoucherInterface::class, $voucherService);

        $services = (new ActiveAdjustments())();
        $this->assertCount(2, $services);
        $this->assertInstanceOf(VoucherInterface::class, $services->first());
        $this->assertInstanceOf(DiscountInterface::class, $services->last());
    }
}
