<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Services;

use Brick\Money\Money;
use Carbon\Carbon;
use Tipoff\Checkout\Contracts\Model\CartInterface;
use Tipoff\Checkout\Contracts\Service\DiscountsService;
use Tipoff\Checkout\Contracts\Service\NullService;
use Tipoff\Checkout\Enums\ServiceType;
use Tipoff\Checkout\Exceptions\ServiceNotImplementedException;
use Tipoff\Support\Enums\AppliesTo;

class NullDiscountService implements DiscountsService, NullService
{
    public function createAmountDiscount(string $name, string $code, Money $amount, AppliesTo $appliesTo, Carbon $expiresAt, int $creatorId)
    {
        throw new ServiceNotImplementedException(ServiceType::DISCOUNT());
    }

    public function createPercentDiscount(string $name, string $code, float $percent, AppliesTo $appliesTo, Carbon $expiresAt, int $creatorId)
    {
        throw new ServiceNotImplementedException(ServiceType::DISCOUNT());
    }

    public function applyCodeToCart(CartInterface $cart, string $code): bool
    {
        return false;
    }

    public function calculateDeductions(CartInterface $cart): Money
    {
        return Money::ofMinor(0, 'USD');
    }
}
