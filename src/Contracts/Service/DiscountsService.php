<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Contracts\Service;

use Brick\Money\Money;
use Carbon\Carbon;
use Tipoff\Checkout\Contracts\Model\CartInterface;
use Tipoff\Support\Enums\AppliesTo;

interface DiscountsService extends CartDeduction
{
    public function createAmountDiscount(string $name, string $code, Money $amount, AppliesTo $appliesTo, Carbon $expiresAt, int $creatorId);

    public function createPercentDiscount(string $name, string $code, float $percent, AppliesTo $appliesTo, Carbon $expiresAt, int $creatorId);

    public function applyCodeToCart(CartInterface $cart, string $code): bool;
}
