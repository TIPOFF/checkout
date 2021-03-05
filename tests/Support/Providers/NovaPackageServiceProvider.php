<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Support\Providers;

use Tipoff\Checkout\Nova\Order;
use Tipoff\Checkout\Nova\OrderItem;
use Tipoff\TestSupport\Providers\BaseNovaPackageServiceProvider;

class NovaPackageServiceProvider extends BaseNovaPackageServiceProvider
{
    public static array $packageResources = [
    ];
}
