<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Services\Cart;

use Illuminate\Support\Facades\DB;
use Tipoff\Checkout\Exceptions\CartNotValidException;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Models\CartItem;
use Tipoff\Support\Contracts\Checkout\Discounts\DiscountInterface;
use Tipoff\Support\Events\Checkout\CartItemPurchaseVerification;

class ApplyDiscounts
{
    public function __invoke(Cart $cart): Cart
    {
        if ($service = findService(DiscountInterface::class)) {
            /** @var DiscountInterface $service */
            $this->resetDiscounts($cart)->calculateAdjustments($service, $cart);
        }

        return $cart;
    }

    private function resetDiscounts(Cart $cart): self
    {
        $cart->cartItems->each(function (CartItem $cartItem) {
            $cartItem->setAmount($cartItem->getAmount()->reset());
        });
        $cart->setShipping($cart->getShipping()->reset());
        $cart->discounts = 0;

        return $this;
    }

    private function calculateAdjustments(DiscountInterface $service, Cart $cart): self
    {
        $service::calculateAdjustments($cart);

        return $this;
    }
}
