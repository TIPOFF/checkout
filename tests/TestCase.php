<?php

namespace Tipoff\Checkout\Tests;

use Laravel\Nova\NovaCoreServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
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
        $app['config']->set('tipoff.model_class.user', Models\User::class);

        // Create stub models for anything not already defined
        foreach (config('tipoff.model_class') as $modelClass) {
            createModelStub($modelClass);
        }

        // Create nova resource stubs for anything not already defined
        foreach (config('tipoff.nova_class') as $alias => $novaClass) {
            if ($modelClass = config('tipoff.model_class.'.$alias)) {
                createNovaResourceStub($novaClass, $modelClass);
            }
        }
    }
}
