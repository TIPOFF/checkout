<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Services;

use Brick\Money\Money;
use Tipoff\Checkout\Contracts\Service\CheckoutService;
use Tipoff\Checkout\Contracts\Service\DiscountsService;
use Tipoff\Checkout\Contracts\Service\VouchersService;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Models\CartItem;

class CheckoutServiceImplementation implements CheckoutService
{
    public function updateTotalCartDeductions(Cart $cart): Cart
    {
        $totalDeductions = $cart->cartItems->reduce(function (Money $totalDeductions, CartItem $cartItem) {
            return $totalDeductions->plus(Money::ofMinor($cartItem->total_deductions, 'USD'));
        }, Money::ofMinor(0, 'USD'));

        $totalDeductions = $totalDeductions->plus(app(VouchersService::class)->calculateDeduction($cart));
        $totalDeductions = $totalDeductions->plus(app(DiscountsService::class)->calculateDeduction($cart));

        $cart->total_deductions = $totalDeductions->getUnscaledAmount()->toInt();
        $cart->save();

        return $cart;
    }
}
