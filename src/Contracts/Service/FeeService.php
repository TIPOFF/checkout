<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Contracts\Service;

use Tipoff\Checkout\Contracts\Model\CartItemInterface;

interface FeeService
{
    public function generateTotalFeesByCartItem(CartItemInterface $cartItem): int;
}
