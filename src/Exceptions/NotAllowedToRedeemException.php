<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Exceptions;

class NotAllowedToRedeemException extends CouponException
{
    /**
     * @var string
     */
    protected $message = 'You cannot use this coupon.';

    /**
     * @var int
     */
    protected $code = 403;
}
