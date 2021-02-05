<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Services;

use Tipoff\Checkout\Contracts\Model\CartItemInterface;
use Tipoff\Checkout\Contracts\Service\FeeService;
use Tipoff\Checkout\Contracts\Service\NullService;

class NullFeeService implements FeeService, NullService
{
    public function generateTotalFeesByCartItem(CartItemInterface $cartItem): int
    {
        return 0;
    }
}
