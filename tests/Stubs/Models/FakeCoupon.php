<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Tests\Stubs\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use MichaelRubel\Couponables\Models\Contracts\CouponContract;
use MichaelRubel\Couponables\Models\Coupon;

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
}
