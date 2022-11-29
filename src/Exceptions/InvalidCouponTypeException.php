<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Exceptions;

class InvalidCouponTypeException extends CouponException
{
    /**
     * @var string
     */
    protected $message = 'The coupon type is invalid.';

    /**
     * @var int
     */
    protected $code = 404;
}
