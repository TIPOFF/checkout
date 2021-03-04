<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Services\Cart;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tipoff\Checkout\Exceptions\InvalidDeductionCodeException;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Services\Cart\ApplyCode;
use Tipoff\Checkout\Services\Cart\FindAdjustmentCode;
use Tipoff\Checkout\Tests\TestCase;
use Tipoff\Support\Contracts\Checkout\CodedCartAdjustment;

class ApplyCodeTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function not_found()
    {
        $findAdjustmentCode = \Mockery::mock(FindAdjustmentCode::class);
        $findAdjustmentCode->shouldReceive('__invoke')
            ->once()
            ->with('ABCD')
            ->andReturnNull();
        $this->app->instance(FindAdjustmentCode::class, $findAdjustmentCode);

        $cart = Cart::factory()->create();

        $this->expectException(InvalidDeductionCodeException::class);
        $this->expectExceptionMessage('Code ABCD is invalid.');

        $service = $this->app->make(ApplyCode::class);
        ($service)($cart, 'ABCD');
    }

    /** @test */
    public function found()
    {
        $deduction = \Mockery::mock(CodedCartAdjustment::class);
        $deduction->shouldReceive('applyToCart')
            ->once()
            ->andReturnSelf();

        $findAdjustmentCode = \Mockery::mock(FindAdjustmentCode::class);
        $findAdjustmentCode->shouldReceive('__invoke')
            ->once()
            ->with('ABCD')
            ->andReturn($deduction);
        $this->app->instance(FindAdjustmentCode::class, $findAdjustmentCode);

        $cart = Cart::factory()->create();

        $service = $this->app->make(ApplyCode::class);
        ($service)($cart, 'ABCD');
    }
}
