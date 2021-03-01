<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Services\Cart;

use Illuminate\Support\Facades\DB;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Models\Order;
use Tipoff\Checkout\Models\OrderItem;
use Tipoff\Checkout\Services\Order\CreateFromCart;
use Tipoff\Support\Events\Checkout\OrderCreated;
use Tipoff\Support\Events\Checkout\OrderItemCreated;

/**
 * - Assemble order/orderitems from cart
 * - Dispatch OrderItemCreated events
 * - Dispatch OrderCreated event
 */
class CompletePurchase
{
    public function __invoke(Cart $cart): Order
    {
        /** @var Order $order */
        $order = DB::transaction(function () use ($cart) {
            // Create order from cart
            $order = app(CreateFromCart::class)($cart);

            // Dispatch events -- allow linked Sellable to make limited adjustments
            $this->notifyOrderItemsCreated($order);

            // Link cart to order prior to soft deletion
            $cart->order()->associate($order);
            $cart->save();

            OrderCreated::dispatch($order);

            $cart->delete();

            return $order;
        });

        return $order;
    }

    private function notifyOrderItemsCreated(Order $order): self
    {
        // Dispatch item creation events
        $order->orderItems->each(function (OrderItem $orderItem) {
            OrderItemCreated::dispatch($orderItem);
            $orderItem->save();
        });

        $order->save();

        return $this;
    }
}
