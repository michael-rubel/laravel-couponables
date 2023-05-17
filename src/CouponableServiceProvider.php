<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables;

use MichaelRubel\Couponables\Commands\MakeCouponCommand;
use MichaelRubel\Couponables\Models\Contracts\CouponContract;
use MichaelRubel\Couponables\Models\Contracts\CouponPivotContract;
use MichaelRubel\Couponables\Models\Coupon;
use MichaelRubel\Couponables\Models\Couponable;
use MichaelRubel\Couponables\Services\Contracts\CouponServiceContract;
use MichaelRubel\Couponables\Services\CouponService;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CouponableServiceProvider extends PackageServiceProvider
{
    /**
     * @var Package
     */
    public Package $package;

    /**
     * Configure the package.
     *
     * @param  Package  $package
     *
     * @return void
     */
    public function configurePackage(Package $package): void
    {
        $this->package = $package
            ->name('laravel-couponables')
            ->hasConfigFile()
            ->hasMigrations([
                'create_coupons_table',
                'create_couponables_table',
            ])->hasCommand(MakeCouponCommand::class);
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function packageRegistered(): void
    {
        $model = config('couponables.model', Coupon::class);
        $this->app->scoped(CouponContract::class, $model);

        $pivot = config('couponables.pivot', Couponable::class);
        $this->app->scoped(CouponPivotContract::class, $pivot);

        $service = config('couponables.service', CouponService::class);
        $this->app->scoped(CouponServiceContract::class, $service);
    }
}
