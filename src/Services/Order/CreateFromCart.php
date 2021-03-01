<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Services\Order;

use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Models\CartItem;
use Tipoff\Checkout\Models\Order;
use Tipoff\Checkout\Models\OrderItem;

/**
 * Create order/orderitems from cart
 */
class CreateFromCart
{
    public function __invoke(Cart $cart): Order
    {
        $order = Order::createFromCart($cart);
        $cart->cartItems()->isRootItem()->each(function (CartItem $cartItem) use ($order) {
            $this->createItemTree($order, $cartItem);
        });

        return $order->refresh();
    }

    private function createItemTree(Order $order, CartItem $cartItem, OrderItem $parentItem = null): void
    {
        $parentItem = OrderItem::createFromCartItem($order, $cartItem, $parentItem);

        $cartItem->children()->each(function (CartItem $cartItem) use ($order, $parentItem) {
            $this->createItemTree($order, $cartItem, $parentItem);
        });
    }
}
