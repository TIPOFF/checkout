<?php

namespace Tipoff\Checkout\Tests;

use Laravel\Nova\NovaCoreServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use ReflectionClass;
use Tipoff\Checkout\CheckoutServiceProvider;
use Tipoff\Checkout\Tests\Support\Models;
use Tipoff\Checkout\Tests\Support\Providers\NovaTestbenchServiceProvider;
use Tipoff\Support\SupportServiceProvider;

class TestCase extends Orchestra
{
    public function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', ['--database' => 'testing'])->run();

        // Create stub tables for stub models to satisfy possible FK dependencies
        foreach (config('tipoff.model_class') as $class) {
            if (method_exists($class, 'createTable')) {
                $class::createTable();
            }
        }
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
        ]);

        $app['config']->set('checkout.nova_class', [
        ]);

        // Create stub models for anything not already defined
        foreach (config('tipoff.model_class') as $class) {
            createModelStub($class);
        }
    }
}
