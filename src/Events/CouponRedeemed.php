<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Events;

use Illuminate\Queue\SerializesModels;

class CouponRedeemed
{
    use SerializesModels;

    /**
     * @return void
     */
    public function __construct(
        private object $user,
        private object $coupon
    ) {
    }
}
