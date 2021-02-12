<?php

declare(strict_types=1);

namespace Tipoff\Checkout;

use Tipoff\Checkout\Contracts\Models\CartInterface;
use Tipoff\Checkout\Contracts\Models\CartItemInterface;
use Tipoff\Checkout\Contracts\Models\OrderInterface;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Models\CartItem;
use Tipoff\Checkout\Models\Order;
use Tipoff\Support\TipoffPackage;
use Tipoff\Support\TipoffServiceProvider;

class CheckoutServiceProvider extends TipoffServiceProvider
{
    public function configureTipoffPackage(TipoffPackage $package): void
    {
        $package
            ->name('checkout')
            ->hasConfigFile();

        $package
            ->hasModelInterfaces([
                CartInterface::class => Cart::class,
                CartItemInterface::class => CartItem::class,
                OrderInterface::class => Order::class,
            ]);
    }
}
