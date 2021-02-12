<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Contracts\Models;

use Brick\Money\Money;

interface CartDeduction
{
    public static function findDeductionByCode(string $code): ?CartDeduction;

    public static function calculateCartDeduction(CartInterface $cart): Money;

    public static function markCartDeductionsAsUsed(CartInterface $cart): void;

    /**
     * @param CartInterface $cart
     * @return array|string[]
     */
    public static function getCodesForCart(CartInterface $cart): array;

    public function applyToCart(CartInterface $cart);
}
