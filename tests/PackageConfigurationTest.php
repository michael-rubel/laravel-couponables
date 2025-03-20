<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Tests;

use MichaelRubel\Couponables\Commands\MakeCouponCommand;
use MichaelRubel\Couponables\CouponableServiceProvider;

class PackageConfigurationTest extends TestCase
{
    /** @test */
    public function test_provider_has_proper_configuration()
    {
        $package = $this->app->register(CouponableServiceProvider::class)->package;

        $this->assertSame('laravel-couponables', $package->name);
        $this->assertSame(['couponables'], $package->configFileNames);
        $this->assertSame([MakeCouponCommand::class], $package->commands);
        $this->assertSame([
            'create_coupons_table',
            'create_couponables_table',
        ], $package->migrationFileNames);
    }
}
