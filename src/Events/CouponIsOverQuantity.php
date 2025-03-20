<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;
use MichaelRubel\Couponables\Models\Contracts\CouponContract;

class CouponIsOverQuantity
{
    use SerializesModels;

    /**
     * @return void
     */
    public function __construct(
        public CouponContract $coupon,
        public ?Model $redeemer = null,
    ) {}
}
