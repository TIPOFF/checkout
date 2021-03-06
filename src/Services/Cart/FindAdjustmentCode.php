<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Services\Cart;

use Tipoff\Support\Contracts\Checkout\CodedCartAdjustment;

class FindAdjustmentCode
{
    public function __invoke(string $code): ?CodedCartAdjustment
    {
        $result = null;
        (new ActiveAdjustments())()
            ->first(function (CodedCartAdjustment $deduction) use ($code, &$result) {
                $result = $deduction::findByCode($code);

                return $result;
            });

        return $result;
    }
}
