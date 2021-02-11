<?php

declare(strict_types=1);

namespace Tipoff\Checkout;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Tipoff\Checkout\Contracts\Models\CartInterface;
use Tipoff\Checkout\Models\Cart;

class CheckoutServiceProvider extends PackageServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        parent::boot();
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('checkout')
            ->hasConfigFile();
    }

    public function registeringPackage()
    {
        $this->app->bind(CartInterface::class, Cart::class);
    }
}
