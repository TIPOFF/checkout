<?php

declare(strict_types=1);

namespace Tipoff\Checkout;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Tipoff\Checkout\Commands\CheckoutCommand;
use Tipoff\Checkout\Contracts\Service\BookingService;
use Tipoff\Checkout\Contracts\Service\CheckoutService;
use Tipoff\Checkout\Contracts\Service\DiscountsService;
use Tipoff\Checkout\Contracts\Service\VouchersService;
use Tipoff\Checkout\Enums\ServiceType;
use Tipoff\Checkout\Services\CheckoutServiceImplementation;
use Tipoff\Checkout\Services\NullBookingService;
use Tipoff\Checkout\Services\NullDiscountService;
use Tipoff\Checkout\Services\NullVoucherService;

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
            return new CheckoutServiceImplementation();
        });

        if (!$this->app->has(DiscountsService::class)) {
            $this->app->singleton(DiscountsService::class, function () {
                return new NullDiscountService();
            });
        }

        if (!$this->app->has(VouchersService::class)) {
            $this->app->singleton(VouchersService::class, function () {
                return new NullVoucherService();
            });
        }

        if (!$this->app->has(BookingService::class)) {
            $this->app->singleton(BookingService::class, function () {
                return new NullBookingService();
            });
        }

        $this->app->alias(ServiceType::DISCOUNT, DiscountsService::class);
        $this->app->alias(ServiceType::VOUCHER, VouchersService::class);
        $this->app->alias(ServiceType::BOOKING, BookingService::class);
    }
}
