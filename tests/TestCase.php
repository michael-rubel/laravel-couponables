<?php

namespace MichaelRubel\Couponables\Tests;

use Illuminate\Foundation\Testing\Concerns\InteractsWithViews;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MichaelRubel\Couponables\CouponableServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    use InteractsWithViews, RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            CouponableServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        config()->set('testing');
    }

    /**
     * Define database migrations.
     *
     * @return void
     */
    protected function defineDatabaseMigrations(): void
    {
        $this->artisan('vendor:publish', [
            '--tag' => 'couponables-migrations',
        ])->run();

        $this->loadMigrationsFrom(__DIR__ . '/Stubs/Migrations');

        $this->artisan('migrate')->run();

        $this->beforeApplicationDestroyed(function () {
            $this->artisan('migrate:reset')->run();
        });
    }
}
