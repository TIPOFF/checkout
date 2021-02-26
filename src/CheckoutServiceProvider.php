<?php

declare(strict_types=1);

namespace Tipoff\Checkout;

use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Models\CartItem;
use Tipoff\Checkout\Models\Order;
use Tipoff\Checkout\Models\OrderItem;
use Tipoff\Checkout\Policies\CartItemPolicy;
use Tipoff\Checkout\Policies\CartPolicy;
use Tipoff\Checkout\Policies\OrderItemPolicy;
use Tipoff\Checkout\Policies\OrderPolicy;
use Tipoff\Support\Contracts\Checkout\CartInterface;
use Tipoff\Support\Contracts\Checkout\CartItemInterface;
use Tipoff\Support\Contracts\Checkout\OrderInterface;
use Tipoff\Support\Contracts\Checkout\OrderItemInterface;
use Tipoff\Support\TipoffPackage;
use Tipoff\Support\TipoffServiceProvider;

class CheckoutServiceProvider extends TipoffServiceProvider
{
    public function configureTipoffPackage(TipoffPackage $package): void
    {
        $package
            ->hasModelInterfaces([
                CartInterface::class => Cart::class,
                CartItemInterface::class => CartItem::class,
                OrderInterface::class => Order::class,
                OrderItemInterface::class => OrderItem::class,
            ])
            ->hasPolicies([
                Cart::class => CartPolicy::class,
                CartItem::class => CartItemPolicy::class,
                Order::class => OrderPolicy::class,
                OrderItem::class => OrderItemPolicy::class,
            ])
            ->hasNovaResources([
                Nova\Order::class,
                Nova\OrderItem::class,
            ])
            ->hasApiRoute('api')
            ->name('checkout')
            ->hasConfigFile();
    }
}
