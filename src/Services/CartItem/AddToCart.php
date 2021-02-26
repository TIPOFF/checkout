<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Services\CartItem;

use Tipoff\Checkout\Exceptions\CartNotValidException;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Models\CartItem;

class AddToCart
{
    public function __invoke(CartItem $cartItem, Cart $cart): CartItem
    {
        // Ensure item is unique
        if ($cart->findItem($cartItem->getSellable(), $cartItem->getItemId())) {
            throw new CartNotValidException();
        }

        // Validate location is allowed
        $cart->setLocationId($cartItem->getLocationId());

        $cart->cartItems()->save($cartItem);

        $cartItem->save();

        $cart->load('cartItems');
        $cart->updatePricing();

        return $cartItem->load('cart');
    }
}
