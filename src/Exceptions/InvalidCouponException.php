<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Exceptions;

class InvalidCouponException extends CouponException
{
    /**
     * @var string
     */
    protected $message = 'The coupon is invalid.';

    /**
     * @var int
     */
    protected $code = 404;
}
