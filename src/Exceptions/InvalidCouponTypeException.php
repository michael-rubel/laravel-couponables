<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Exceptions;

class InvalidCouponTypeException extends CouponException
{
    /**
     * @var string
     */
    protected $message = 'The coupon type is invalid. Take a look at `CouponContract` and review your `type` table column value.';

    /**
     * @var int
     */
    protected $code = 404;
}
