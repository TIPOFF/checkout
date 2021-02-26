<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Services\CartItem;

use Tipoff\Checkout\Exceptions\CartNotValidException;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Models\CartItem;
use Tipoff\Support\Events\Checkout\CartItemUpdated;
use Tipoff\Support\Events\Checkout\CartUpdated;

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

        CartItemUpdated::dispatch($cartItem);
        $cartItem->save();

        $cart->load('cartItems');
        $cart->updatePricing();

        CartUpdated::dispatch($cart);

        return $cartItem->load('cart');
    }
}
