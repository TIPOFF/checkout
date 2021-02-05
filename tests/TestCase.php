<?php

namespace Tipoff\Checkout\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Laravel\Nova\NovaCoreServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use ReflectionClass;
use Tipoff\Checkout\CheckoutServiceProvider;
use Tipoff\Checkout\Tests\Support\Models;
use Tipoff\Checkout\Tests\Support\Nova;
use Tipoff\Checkout\Tests\Support\Providers\NovaTestbenchServiceProvider;
use Tipoff\Support\SupportServiceProvider;

class TestCase extends Orchestra
{
    public function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Tipoff\\Checkout\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        $this->artisan('migrate', ['--database' => 'testing'])->run();
    }

    protected function getPackageProviders($app)
    {
        return [
            NovaCoreServiceProvider::class,
            NovaTestbenchServiceProvider::class,
            SupportServiceProvider::class,
            CheckoutServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        // Fix for Nova guessing namespace for local resources
        $property = (new ReflectionClass($app))->getProperty('namespace');
        $property->setAccessible(true);
        $property->setValue($app, 'Tipoff\\Vouchers\\');

        $app['config']->set('checkout.model_class', [
            'user' => Models\User::class,
            'location' => Models\Location::class,
            'customer' => Models\Customer::class,
            'booking' => Models\Booking::class,
            'room' => Models\Room::class,
            'rate' => Models\Rate::class,
            'tax' => Models\Tax::class,
            'fee' => Models\Fee::class,
        ]);

        $app['config']->set('checkout.nova_class', [
        ]);

        // Create stub tables to satisfy FK dependencies
        foreach (config('checkout.model_class') as $class) {
            $class::createTable();
        }
    }
}
