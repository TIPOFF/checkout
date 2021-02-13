<?php

declare(strict_types=1);

namespace Tipoff\Checkout;

use Tipoff\Checkout\Models\Checkout;
use Tipoff\Checkout\Policies\CheckoutPolicy;
use Tipoff\Support\TipoffPackage;
use Tipoff\Support\TipoffServiceProvider;

class CheckoutServiceProvider extends TipoffServiceProvider
{
    public function configureTipoffPackage(TipoffPackage $package): void
    {
        $package
            ->hasPolicies([
                Checkout::class => CheckoutPolicy::class,
            ])
            ->name('checkout')
            ->hasConfigFile();
    }
}
