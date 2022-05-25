<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Events;

use Illuminate\Queue\SerializesModels;

class NotAllowedToRedeem
{
    use SerializesModels;

    /**
     * @return void
     */
    public function __construct(
        private object $coupon,
        private object $redeemer
    ) {
    }
}