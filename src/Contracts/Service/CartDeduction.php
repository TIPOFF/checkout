<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Contracts\Service;

use Brick\Money\Money;
use Tipoff\Checkout\Contracts\Model\CartInterface;

interface CartDeduction
{
    public function calculateDeductions(CartInterface $cart): Money;
}
