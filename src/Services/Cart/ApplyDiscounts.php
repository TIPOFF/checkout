<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Services\Cart;

use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Models\CartItem;
use Tipoff\Support\Contracts\Checkout\Discounts\DiscountInterface;

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
            $cartItem->setAmountEach($cartItem->getAmountTotal()->reset());
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
