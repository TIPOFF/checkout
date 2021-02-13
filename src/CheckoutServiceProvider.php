<?php

declare(strict_types=1);

namespace Tipoff\Checkout;

use Tipoff\Checkout\Contracts\Models\CartInterface;	
use Tipoff\Checkout\Contracts\Models\CartItemInterface;	
use Tipoff\Checkout\Contracts\Models\OrderInterface;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Models\CartItem;
use Tipoff\Checkout\Models\Order;
use Tipoff\Checkout\Policies\CartItemPolicy;
use Tipoff\Checkout\Policies\CartPolicy;
use Tipoff\Checkout\Policies\OrderPolicy;
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
            ]);
            ->hasPolicies([
                Cart::class => CartPolicy::class,
                CartItem::class => CartItemPolicy::class,
                Order::class => OrderPolicy::class,
            ])
            ->name('checkout')
            ->hasConfigFile();
    }
}
