<?php

namespace MichaelRubel\Couponables\Exceptions;

class CouponExpiredException extends \Exception
{
    /**
     * @var string
     */
    protected $message = 'The coupon is expired.';

    /**
     * @var int
     */
    protected $code = 400;
}
