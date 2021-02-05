<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Services;

use Tipoff\Checkout\Contracts\Model\CartItemInterface;
use Tipoff\Checkout\Contracts\Service\NullService;
use Tipoff\Checkout\Contracts\Service\TaxService;

class NullTaxService implements TaxService, NullService
{
    public function generateTotalTaxesByCartItem(CartItemInterface $cartItem): int
    {
        return 0;
    }
}
