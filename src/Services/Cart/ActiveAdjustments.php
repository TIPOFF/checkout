<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Services\Cart;

use Illuminate\Support\Collection;
use Tipoff\Support\Contracts\Checkout\Discounts\DiscountInterface;
use Tipoff\Support\Contracts\Checkout\Vouchers\VoucherInterface;

class ActiveAdjustments
{
    private static array $deductionTypes = [
        VoucherInterface::class,
        DiscountInterface::class,
    ];

    public function __invoke(): Collection
    {
        return collect(static::$deductionTypes)
            ->filter(function (string $type) {
                return app()->has($type);
            })
            ->map(function (string $type) {
                return app($type);
            });
    }
}
