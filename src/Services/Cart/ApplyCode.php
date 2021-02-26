<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Services\Cart;

use Illuminate\Support\Collection;
use Tipoff\Checkout\Exceptions\InvalidDeductionCodeException;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Support\Contracts\Checkout\CodedCartAdjustment;
use Tipoff\Support\Contracts\Checkout\Discounts\DiscountInterface;
use Tipoff\Support\Contracts\Checkout\Vouchers\VoucherInterface;

class ApplyCode
{
    private static array $deductionTypes = [
        VoucherInterface::class,
        DiscountInterface::class,
    ];

    public function __invoke(Cart $cart, string $code): Cart
    {
        $deduction = $this->findDeductionByCode($code);

        if (empty($deduction)) {
            throw new InvalidDeductionCodeException($code);
        }

        $deduction->applyToCart($cart);

        return $cart;
    }

    protected static function activeAdjustments(): Collection
    {
        return collect(static::$deductionTypes)
            ->filter(function (string $type) {
                return app()->has($type);
            })
            ->map(function (string $type) {
                return app($type);
            });
    }

    protected function findDeductionByCode(string $code): ?CodedCartAdjustment
    {
        return static::activeAdjustments()
            ->first(function (CodedCartAdjustment $deduction) use ($code) {
                return $deduction::findByCode($code);
            });
    }
}
