<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Tests\Stubs\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use MichaelRubel\Couponables\Models\Contracts\CouponContract;
use MichaelRubel\Couponables\Models\Coupon;
use MichaelRubel\Couponables\Tests\Stubs\Factories\FakeCouponFactory;

class FakeCoupon extends Coupon implements CouponContract
{
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'data_test' => 'collection',
    ];

    /**
     * Create a new factory instance for the model.
     *
     * @return Factory<Coupon>
     */
    protected static function newFactory(): Factory
    {
        parent::newFactory();

        return FakeCouponFactory::new();
    }
}
