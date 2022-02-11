<?php

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

];
