<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Exceptions;

class InvalidCouponValueException extends CouponException
{
    /**
     * @var string
     */
    protected $message = 'The coupon value cannot be zero or less.';

    /**
     * @var int
     */
    protected $code = 500;
}
