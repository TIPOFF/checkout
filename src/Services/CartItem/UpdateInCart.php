<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Services\CartItem;

use Tipoff\Checkout\Exceptions\CartNotValidException;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Models\CartItem;

class UpdateInCart
{
    public function __invoke(CartItem $cartItem, Cart $cart): CartItem
    {
        // Validate item already exists in is in this cart
        if (! $cartItem->getCart() || ($cartItem->getCart()->getId() !== $cart->id)) {
            throw new CartNotValidException();
        }

        // Validate location is allowed
        $cart->setLocationId($cartItem->getLocationId());

        $cartItem->save();

        $cart->load('cartItems');
        $cart->updatePricing();

        return $cartItem->load('cart');
    }
}
