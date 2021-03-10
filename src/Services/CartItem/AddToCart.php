<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Services\CartItem;

use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Models\CartItem;

class AddToCart
{
    public function __invoke(CartItem $cartItem, Cart $cart): CartItem
    {
        // Ensure item is unique
        /** @var CartItem $findItem */
        $findItem = $cart->findItem($cartItem->getSellable(), $cartItem->getItemId());
        if ($findItem) {
            $findItem->delete();
            $cart->load('cartItems');
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
