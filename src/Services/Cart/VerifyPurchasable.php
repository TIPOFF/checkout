<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Services\Cart;

use Illuminate\Support\Facades\DB;
use Tipoff\Checkout\Exceptions\CartNotValidException;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Models\CartItem;
use Tipoff\Support\Events\Checkout\CartItemPurchaseVerification;

/**
 * Performs final verification that cart is purchasable.  This includes
 * - basic validation of cart and item expirations
 * - dispatch of CartItemPurchased event allowing Sellable ability to abort
 * - confirmation application of discounts and credits results in identical total
 */
class VerifyPurchasable
{
    public function __invoke(Cart $cart): Cart
    {
        DB::transaction(function () use ($cart) {
            $this->verifyNotEmpty($cart)
                ->verifyNoExpiredItems($cart)
                ->verifyItems($cart)
                ->verifyNoPriceChange($cart);
        });

        return $cart;
    }

    private function verifyNotEmpty(Cart $cart): self
    {
        // Must have at least one item
        if ($cart->cartItems->isEmpty()) {
            throw new CartNotValidException();
        }

        return $this;
    }

    private function verifyNoExpiredItems(Cart $cart): self
    {
        // All items must be active
        if ($cart->cartItems->first->isExpired()) {
            throw new CartNotValidException();
        }

        return $this;
    }

    private function verifyItems(Cart $cart): self
    {
        // Dispatch events to ensure all items remain purchasable
        $cart->cartItems->each(function (CartItem $cartItem) {
            CartItemPurchaseVerification::dispatch($cartItem);
        });

        return $this;
    }

    private function verifyNoPriceChange(Cart $cart): self
    {
        // Validate all discounts and vouchers remain valid
        $originalTotal = $cart->getPricingDetail();
        $cart->updatePricing();
        if (! $originalTotal->isEqual($cart->getPricingDetail())) {
            throw new CartNotValidException();
        }

        return $this;
    }
}
