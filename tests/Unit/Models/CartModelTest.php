<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Models;

use Brick\Money\Money;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tipoff\Checkout\Contracts\Models\DiscountInterface;
use Tipoff\Checkout\Contracts\Models\VoucherInterface;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Models\CartItem;
use Tipoff\Checkout\Tests\TestCase;

class CartModelTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function create()
    {
        $cart = Cart::factory()->create();
        $this->assertNotNull($cart);
    }

    /** @test */
    public function cart_item_total_deductions_single_item()
    {
        /** @var Cart $cart */
        $cart = Cart::factory()->create();

        CartItem::factory()->create([
            'cart_id' => $cart,
            'total_deductions' => 1000,
        ]);
        $cart->refresh();

        $cart->updateTotalDeductions();
        $this->assertEquals(1000, $cart->total_deductions);
    }

    /** @test */
    public function cart_item_total_deductions_mixed_items()
    {
        /** @var Cart $cart */
        $cart = Cart::factory()->create();

        CartItem::factory()->create([
            'cart_id' => $cart,
            'total_deductions' => 1000,
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart,
            'total_deductions' => null,
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart,
            'total_deductions' => 500,
        ]);

        $cart->refresh();

        $cart->updateTotalDeductions();
        $this->assertEquals(1500, $cart->total_deductions);
    }

    /** @test */
    public function cart_deductions_no_services()
    {
        /** @var Cart $cart */
        $cart = Cart::factory()->create();

        $cart->updateTotalDeductions();
        $this->assertEquals(0, $cart->total_deductions);
    }

    /** @test */
    public function cart_voucher_deductions_with_service()
    {
        $service = \Mockery::mock(VoucherInterface::class);
        $service->shouldReceive('calculateCartDeduction')
            ->once()
            ->andReturn(Money::ofMinor(1000, 'USD'));

        $this->app->instance(VoucherInterface::class, $service);

        /** @var Cart $cart */
        $cart = Cart::factory()->create();

        $cart->updateTotalDeductions();
        $this->assertEquals(1000, $cart->total_deductions);
    }

    /** @test */
    public function cart_discount_deductions_with_service()
    {
        $service = \Mockery::mock(DiscountInterface::class);
        $service->shouldReceive('calculateCartDeduction')
            ->once()
            ->andReturn(Money::ofMinor(1000, 'USD'));

        $this->app->instance(DiscountInterface::class, $service);

        /** @var Cart $cart */
        $cart = Cart::factory()->create();

        $cart->updateTotalDeductions();
        $this->assertEquals(1000, $cart->total_deductions);
    }

    /** @test */
    public function update_cart_total_deductions()
    {
        $service = \Mockery::mock(DiscountInterface::class);
        $service->shouldReceive('calculateCartDeduction')
            ->once()
            ->andReturn(Money::ofMinor(1000, 'USD'));

        $this->app->instance(DiscountInterface::class, $service);

        $service = \Mockery::mock(VoucherInterface::class);
        $service->shouldReceive('calculateCartDeduction')
            ->once()
            ->andReturn(Money::ofMinor(300, 'USD'));

        $this->app->instance(VoucherInterface::class, $service);

        /** @var Cart $cart */
        $cart = Cart::factory()->create();

        CartItem::factory()->create([
            'cart_id' => $cart,
            'total_deductions' => 50,
        ]);

        $cart->refresh();

        $cart->updateTotalDeductions();
        $this->assertEquals(1350, $cart->total_deductions);
    }

    /** @test */
    public function apply_code_no_service()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Code ABCDE is invalid.');

        /** @var Cart $cart */
        $cart = Cart::factory()->create();

        $cart->applyDeductionCode('ABCDE');
    }

    /** @test */
    public function apply_voucher_code_with_service()
    {
        /** @var Cart $cart */
        $cart = Cart::factory()->create();

        $service = \Mockery::mock(VoucherInterface::class);
        $service->shouldReceive('findDeductionByCode')
            ->once()
            ->with('ABCDE')
            ->andReturnSelf();
        $service->shouldReceive('applyToCart')
            ->once()
            ->with($cart);

        $service->shouldReceive('calculateCartDeduction')
            ->once()
            ->andReturn(Money::ofMinor(1000, 'USD'));

        $this->app->instance(VoucherInterface::class, $service);

        $cart = $cart->applyDeductionCode('ABCDE');

        $this->assertEquals(1000, $cart->total_deductions);
    }

    /** @test */
    public function apply_discount_code_with_service()
    {
        /** @var Cart $cart */
        $cart = Cart::factory()->create();

        $service = \Mockery::mock(DiscountInterface::class);
        $service->shouldReceive('findDeductionByCode')
            ->once()
            ->with('ABCDE')
            ->andReturnSelf();
        $service->shouldReceive('applyToCart')
            ->once()
            ->with($cart);

        $service->shouldReceive('calculateCartDeduction')
            ->once()
            ->andReturn(Money::ofMinor(1300, 'USD'));

        $this->app->instance(DiscountInterface::class, $service);

        $cart = $cart->applyDeductionCode('ABCDE');

        $this->assertEquals(1300, $cart->total_deductions);
    }
}
