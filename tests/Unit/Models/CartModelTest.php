<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Models;

use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Models\CartItem;
use Tipoff\Checkout\Models\Order;
use Tipoff\Checkout\Tests\TestCase;
use Tipoff\Support\Contracts\Models\BookingInterface;
use Tipoff\Support\Contracts\Models\PaymentInterface;
use Tipoff\Support\Contracts\Models\RateInterface;
use Tipoff\Support\Contracts\Models\SlotInterface;
use Tipoff\Support\Contracts\Services\BookingService;
use Tipoff\Support\Contracts\Services\DiscountService;
use Tipoff\Support\Contracts\Services\RateService;
use Tipoff\Support\Contracts\Services\VoucherService;

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

        $amount = $cart->getCartItemTotalDeductions();
        $this->assertEquals(1000, $amount->getUnscaledAmount()->toInt());
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

        $amount = $cart->getCartItemTotalDeductions();
        $this->assertEquals(1500, $amount->getUnscaledAmount()->toInt());
    }

    /** @test */
    public function cart_voucher_deductions_no_service()
    {
        /** @var Cart $cart */
        $cart = Cart::factory()->create();

        $amount = $cart->getVoucherDeductions();
        $this->assertEquals(0, $amount->getUnscaledAmount()->toInt());
    }

    /** @test */
    public function cart_voucher_deductions_with_service()
    {
        $service = \Mockery::mock(VoucherService::class);
        $service->shouldReceive('calculateVoucherDeductions')
            ->once()
            ->andReturn(Money::ofMinor(1000, 'USD'));

        $this->app->instance(VoucherService::class, $service);

        /** @var Cart $cart */
        $cart = Cart::factory()->create();

        $amount = $cart->getVoucherDeductions();
        $this->assertEquals(1000, $amount->getUnscaledAmount()->toInt());
    }

    /** @test */
    public function cart_discount_deductions_no_service()
    {
        /** @var Cart $cart */
        $cart = Cart::factory()->create();

        $amount = $cart->getDiscountDeductions();
        $this->assertEquals(0, $amount->getUnscaledAmount()->toInt());
    }

    /** @test */
    public function cart_discount_deductions_with_service()
    {
        $service = \Mockery::mock(DiscountService::class);
        $service->shouldReceive('calculateDiscountDeductions')
            ->once()
            ->andReturn(Money::ofMinor(1000, 'USD'));

        $this->app->instance(DiscountService::class, $service);

        /** @var Cart $cart */
        $cart = Cart::factory()->create();

        $amount = $cart->getDiscountDeductions();
        $this->assertEquals(1000, $amount->getUnscaledAmount()->toInt());
    }

    /** @test */
    public function update_cart_total_deductions()
    {
        $service = \Mockery::mock(DiscountService::class);
        $service->shouldReceive('calculateDiscountDeductions')
            ->once()
            ->andReturn(Money::ofMinor(1000, 'USD'));

        $this->app->instance(DiscountService::class, $service);

        $service = \Mockery::mock(VoucherService::class);
        $service->shouldReceive('calculateVoucherDeductions')
            ->once()
            ->andReturn(Money::ofMinor(300, 'USD'));

        $this->app->instance(VoucherService::class, $service);

        /** @var Cart $cart */
        $cart = Cart::factory()->create();

        CartItem::factory()->create([
            'cart_id' => $cart,
            'total_deductions' => 50,
        ]);

        $cart->refresh();

        $cart = $cart->updateCartTotalDeductions();

        $this->assertEquals(1350, $cart->total_deductions);
    }

    /** @test */
    public function apply_voucher_code_no_service()
    {
        /** @var Cart $cart */
        $cart = Cart::factory()->create();

        $cart = $cart->applyVoucherCode('ABCD');

        $this->assertEquals(0, $cart->total_deductions);
    }

    /** @test */
    public function apply_voucher_code_with_service()
    {
        /** @var Cart $cart */
        $cart = Cart::factory()->create();

        $service = \Mockery::mock(VoucherService::class);
        $service->shouldReceive('applyCodeToCart')
            ->once()
            ->with($cart, 'ABCD')
            ->andReturnTrue();

        $service->shouldReceive('calculateVoucherDeductions')
            ->once()
            ->andReturn(Money::ofMinor(1000, 'USD'));

        $this->app->instance(VoucherService::class, $service);

        $cart = $cart->applyVoucherCode('ABCD');

        $this->assertEquals(1000, $cart->total_deductions);
    }

    /** @test */
    public function apply_discount_code_no_service()
    {
        /** @var Cart $cart */
        $cart = Cart::factory()->create();

        $cart = $cart->applyDiscountCode('ABCD');

        $this->assertEquals(0, $cart->total_deductions);
    }

    /** @test */
    public function apply_discount_code_with_service()
    {
        /** @var Cart $cart */
        $cart = Cart::factory()->create();

        $service = \Mockery::mock(DiscountService::class);
        $service->shouldReceive('applyCodeToCart')
            ->once()
            ->with($cart, 'ABCD')
            ->andReturnTrue();

        $service->shouldReceive('calculateDiscountDeductions')
            ->once()
            ->andReturn(Money::ofMinor(1300, 'USD'));

        $this->app->instance(DiscountService::class, $service);

        $cart = $cart->applyDiscountCode('ABCD');

        $this->assertEquals(1300, $cart->total_deductions);
    }

    /** @test */
    public function process_order()
    {
        $customer = app('customer')::factory()->create();

        $payment = \Mockery::mock(PaymentInterface::class);
        $payment->shouldReceive('getCustomer->getId')
            ->once()
            ->andReturn($customer->id);
        $payment->shouldReceive('setOrder')
            ->once()
            ->andReturnSelf();

        $rate = \Mockery::mock(RateInterface::class);
        $rate->shouldReceive('getAmount')->andReturn(1000);

        $service = \Mockery::mock(RateService::class);
        $service->shouldReceive('getRate')->andReturn($rate);

        $this->app->instance(RateService::class, $service);

        $slot = \Mockery::mock(SlotInterface::class);
        $slot->shouldReceive('setHold')->once();
        $slot->shouldReceive('releaseHold')->once();
        $slot->shouldReceive('getId')->andReturn(5);

        $service = \Mockery::mock(BookingService::class);
        $service->shouldReceive('resolveSlot')
            ->andReturn($slot);

        $service->shouldReceive('createBooking')
            ->andReturn(\Mockery::mock(BookingInterface::class));

        $this->app->instance(BookingService::class, $service);

        /** @var Cart $cart */
        $cart = Cart::factory()->create([
            'expires_at' => Carbon::now()->addDay(),
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart,
        ]);

        $cart->refresh()
            ->generatePricing()
            ->save();

        $order = $cart->processOrder($payment);

        $this->assertNotNull($order);
        $this->assertInstanceOf(Order::class, $order);
    }
}
