<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Support\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Nova\Nova;
use Laravel\Nova\NovaApplicationServiceProvider;
use Tipoff\Checkout\Nova\Order;

class NovaTestbenchServiceProvider extends NovaApplicationServiceProvider
{
    protected function resources()
    {
        Nova::resources(array_merge(config('checkout.nova_class'), [
            Order::class,
        ]));
    }

    protected function routes()
    {
        Nova::routes()
            ->register();
    }

    /**
     * Register the Nova gate.
     *
     * This gate determines who can access Nova in non-local environments.
     *
     * @return void
     */
    protected function gate()
    {
        Gate::define('viewNova', function ($user) {
            return true;
        });
    }
}
