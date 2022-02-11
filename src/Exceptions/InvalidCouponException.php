<?php

namespace MichaelRubel\Couponables\Exceptions;

class InvalidCouponException extends \Exception
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
