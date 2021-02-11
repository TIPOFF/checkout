<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Contracts\Models;

use Brick\Money\Money;

interface CartDeduction
{
    public static function findDeductionByCode(string $code): ?CartDeduction;

    public static function calculateCartDeduction(CartInterface $cart): Money;

    public static function markCartDeductionsAsUsed(CartInterface $cart): void;

    public function applyToCart(CartInterface $cart);

    /**
     * @param CartInterface $cart
     * @return array|string[]
     */
    public function getCodesForCart(CartInterface $cart): array;
}
