<?php

namespace Tipoff\Checkout;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Tipoff\Checkout\Commands\CheckoutCommand;

class CheckoutServiceProvider extends PackageServiceProvider
{
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
            ->hasViews()
            ->hasMigration('create_checkout_table')
            ->hasCommand(CheckoutCommand::class);
    }
}
