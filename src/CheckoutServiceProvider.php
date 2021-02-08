<?php

declare(strict_types=1);

namespace Tipoff\Checkout;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Tipoff\Checkout\Commands\CheckoutCommand;
use Tipoff\Checkout\Services\CheckoutService;

class CheckoutServiceProvider extends PackageServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        parent::boot();
    }

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('checkout')
            ->hasConfigFile()
            ->hasCommand(CheckoutCommand::class);
    }

    public function registeringPackage()
    {
        $this->app->singleton(CheckoutService::class, function () {
            return new CheckoutService();
        });
    }
}
