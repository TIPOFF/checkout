<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Services\Cart;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Models\CartItem;
use Tipoff\Checkout\Services\Cart\ApplyCredits;
use Tipoff\Checkout\Services\Cart\ApplyDiscounts;
use Tipoff\Checkout\Services\Cart\ApplyTaxes;
use Tipoff\Checkout\Tests\Support\Traits\InteractsWithCarts;
use Tipoff\Checkout\Tests\TestCase;
use Tipoff\Support\Contracts\Checkout\Discounts\DiscountInterface;
use Tipoff\Support\Contracts\Checkout\Vouchers\VoucherInterface;
use Tipoff\Support\Contracts\Taxes\TaxRequest;
use Tipoff\Support\Contracts\Taxes\TaxRequestItem;

class ApplyTaxesTest extends TestCase
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
        $item->setTax(123)->save();

        $this->cart->save();

        $service = $this->app->make(ApplyTaxes::class);
        ($service)($this->cart);

        $this->assertEquals(123, $this->cart->tax);
        $this->assertEquals(123, $this->cart->cartItems->first()->getTax());
    }

    /** @test */
    public function with_service_taxes_reset()
    {
        $this->setupCart();

        $this->addCartItems([
            [500, 1],
        ]);

        /** @var CartItem $item */
        $item = $this->cart->cartItems->first();
        $item->setTax(123)->save();

        $this->cart->save();

        $this->assertEquals(123, $this->cart->tax);
        $this->assertEquals(123, $this->cart->cartItems->first()->getTax());

        $taxService = \Mockery::mock(TaxRequest::class);
        $taxService->shouldReceive('createTaxRequest')->once()->andReturnSelf();
        $taxService->shouldReceive('createTaxRequestItem');
        $taxService->shouldReceive('calculateTax')->once();
        $taxService->shouldReceive('getTaxRequestItem')->once()->andReturnNull();
        $this->app->instance(TaxRequest::class, $taxService);

        $service = $this->app->make(ApplyTaxes::class);
        ($service)($this->cart);

        $this->assertEquals(0, $this->cart->tax);
        $this->assertEquals(0, $this->cart->cartItems->first()->getTax());
    }

    /** @test */
    public function taxes_are_applied()
    {
        $this->setupCart();

        $this->addCartItems([
            [500, 1],
        ]);

        /** @var CartItem $item */
        $item = $this->cart->cartItems->first();
        $item->setLocationId(1)->setTaxCode('ABC')->save();

        $taxService = \Mockery::mock(TaxRequest::class);
        $taxService->shouldReceive('createTaxRequest')->once()->andReturnSelf();
        $taxService->shouldReceive('createTaxRequestItem');
        $taxService->shouldReceive('calculateTax')->once();
        $taxService->shouldReceive('getTaxRequestItem')->once()->andReturnUsing(function () {
            $result = \Mockery::mock(TaxRequestItem::class);
            $result->shouldReceive('getTax')->once()->andReturn(123);

            return $result;
        });
        $this->app->instance(TaxRequest::class, $taxService);

        $service = $this->app->make(ApplyTaxes::class);
        ($service)($this->cart);

        $this->cart->save();
        $this->assertEquals(123, $this->cart->tax);
        $this->assertEquals(123, $this->cart->cartItems->first()->getTax());
    }
}
