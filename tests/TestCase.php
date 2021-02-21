<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests;

use Laravel\Nova\NovaCoreServiceProvider;
use Spatie\Permission\PermissionServiceProvider;
use Tipoff\Authorization\AuthorizationServiceProvider;
use Tipoff\Checkout\CheckoutServiceProvider;
use Tipoff\Checkout\Tests\Support\Providers\NovaPackageServiceProvider;
use Tipoff\EscapeRoom\EscapeRoomServiceProvider;
use Tipoff\Fees\FeesServiceProvider;
use Tipoff\Locations\LocationsServiceProvider;
use Tipoff\Payments\PaymentsServiceProvider;
use Tipoff\Scheduling\SchedulingServiceProvider;
use Tipoff\Support\SupportServiceProvider;
use Tipoff\Taxes\TaxesServiceProvider;
use Tipoff\TestSupport\BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            NovaCoreServiceProvider::class,
            NovaPackageServiceProvider::class,
            SupportServiceProvider::class,
            EscapeRoomServiceProvider::class,
            LocationsServiceProvider::class,
            PaymentsServiceProvider::class,
            SchedulingServiceProvider::class,
            FeesServiceProvider::class,
            TaxesServiceProvider::class,
            AuthorizationServiceProvider::class,
            PermissionServiceProvider::class,
            CheckoutServiceProvider::class,
        ];
    }
}
