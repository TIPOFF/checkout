<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Services\Cart;

use Tipoff\Checkout\Exceptions\InvalidDeductionCodeException;
use Tipoff\Checkout\Models\Cart;

class ApplyCode
{
    public function __invoke(Cart $cart, string $code): Cart
    {
        $deduction = app(FindAdjustmentCode::class)($code);

        if (empty($deduction)) {
            throw new InvalidDeductionCodeException($code);
        }

        $deduction->applyToCart($cart);

        return $cart;
    }
}
