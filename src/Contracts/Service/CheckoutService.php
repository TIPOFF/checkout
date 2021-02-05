<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Contracts\Service;

use Tipoff\Checkout\Models\Cart;

interface CheckoutService
{
    public function updateTotalCartDeductions(Cart $cart): Cart;
}
