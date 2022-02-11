<?php

namespace MichaelRubel\Couponables\Exceptions;

class CouponExpiredException extends \Exception
{
    /**
     * @var string
     */
    protected $message = 'Invalid coupon was passed.';

    /**
     * @var int
     */
    protected $code = 400;
}
