<?php

declare(strict_types=1);

return [

    /*
     |--------------------------------------------------------------------------
     | Laravel Couponables Configuration
     |--------------------------------------------------------------------------
     |
     | Model to use by the package.
     |
     | Default: `\MichaelRubel\Couponables\Models\Coupon::class`
     */

    'model' => \MichaelRubel\Couponables\Models\Coupon::class,

    /*
    | Model table name.
    |
    | Default: `coupons`
    */

    'table' => 'coupons',

    /*
    | Polymorphic pivot model.
    |
    | Default: `\MichaelRubel\Couponables\Models\Couponable::class`
    */

    'pivot' => \MichaelRubel\Couponables\Models\Couponable::class,

    /*
    | Polymorphic pivot table name.
    |
    | Default: `couponables`
    */

    'pivot_table' => 'couponables',

    /*
    | Service class to use by the package.
    |
    | Default: `\MichaelRubel\Couponables\Services\CouponService::class`
    */

    'service' => \MichaelRubel\Couponables\Services\CouponService::class,

    /*
    | Rounding precision if you use calculations.
    |
    | See: `MichaelRubel\Couponables\Traits\Concerns\CalculatesCosts`
    |
    | Default: 2
    */

    'round' => 2,

    'round_mode' => PHP_ROUND_HALF_UP,

    /*
    | Maximum allowed value to be returned by the calculations.
    |
    | See: `MichaelRubel\Couponables\Traits\Concerns\CalculatesCosts`
    |
    | Default: 0
    */

    'max' => 0,

];
