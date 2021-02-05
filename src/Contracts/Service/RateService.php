<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Contracts\Service;

use Tipoff\Checkout\Contracts\Model\CartItemInterface;

interface RateService
{
    public function getAmount(CartItemInterface $cartItem, int $participants, bool $isPrivate): int;
}
