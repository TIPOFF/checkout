<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests;

use Laravel\Nova\NovaCoreServiceProvider;
use Tipoff\Checkout\CheckoutServiceProvider;
use Tipoff\Checkout\Tests\Support\Providers\NovaPackageServiceProvider;
use Tipoff\Discounts\DiscountsServiceProvider;
use Tipoff\EscapeRoom\EscapeRoomServiceProvider;
use Tipoff\Fees\FeesServiceProvider;
use Tipoff\Payments\PaymentsServiceProvider;
use Tipoff\Scheduling\SchedulingServiceProvider;
use Tipoff\Support\SupportServiceProvider;
use Tipoff\Taxes\TaxesServiceProvider;
use Tipoff\TestSupport\BaseTestCase;
use Tipoff\Vouchers\VouchersServiceProvider;

class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            NovaCoreServiceProvider::class,
            NovaPackageServiceProvider::class,
            SupportServiceProvider::class,
            DiscountsServiceProvider::class,
            VouchersServiceProvider::class,
            EscapeRoomServiceProvider::class,
            PaymentsServiceProvider::class,
            SchedulingServiceProvider::class,
            FeesServiceProvider::class,
            TaxesServiceProvider::class,
            CheckoutServiceProvider::class,
        ];
    }
}
