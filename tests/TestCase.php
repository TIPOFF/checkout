<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests;

use Laravel\Nova\NovaCoreServiceProvider;
use Spatie\Fractal\FractalServiceProvider;
use Spatie\Permission\PermissionServiceProvider;
use Tipoff\Addresses\AddressesServiceProvider;
use Tipoff\Authorization\AuthorizationServiceProvider;
use Tipoff\Checkout\CheckoutServiceProvider;
use Tipoff\Locations\LocationsServiceProvider;
use Tipoff\Statuses\StatusesServiceProvider;
use Tipoff\Support\SupportServiceProvider;

use Tipoff\TestSupport\BaseTestCase;
use Tipoff\TestSupport\Providers\NovaPackageServiceProvider;

class TestCase extends BaseTestCase
{
    protected bool $stubNovaResources = false;

    protected function getPackageProviders($app)
    {
        return [
            NovaCoreServiceProvider::class,
            NovaPackageServiceProvider::class,
            SupportServiceProvider::class,
            LocationsServiceProvider::class,
            AddressesServiceProvider::class,
            StatusesServiceProvider::class,
            AuthorizationServiceProvider::class,
            PermissionServiceProvider::class,
            CheckoutServiceProvider::class,
            FractalServiceProvider::class,
        ];
    }
}
