<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Tests\Stubs\Models;

use MichaelRubel\Couponables\Models\Contracts\CouponPivotContract;
use MichaelRubel\Couponables\Models\Couponable;

class FakeCouponable extends Couponable implements CouponPivotContract
{
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'used_at' => 'datetime',
    ];

    /**
     * @return string
     */
    public function getRedeemedAtColumn(): string
    {
        return 'used_at';
    }
}
