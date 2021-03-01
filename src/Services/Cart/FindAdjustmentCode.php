<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Services\Cart;

use Tipoff\Support\Contracts\Checkout\CodedCartAdjustment;
use Tipoff\Support\Contracts\Checkout\Discounts\DiscountInterface;
use Tipoff\Support\Contracts\Checkout\Vouchers\VoucherInterface;

class FindAdjustmentCode
{
    public function __invoke(string $code): ?CodedCartAdjustment
    {
        return (new ActiveAdjustments())()
            ->first(function (CodedCartAdjustment $deduction) use ($code) {
                return $deduction::findByCode($code);
            });
    }
}
