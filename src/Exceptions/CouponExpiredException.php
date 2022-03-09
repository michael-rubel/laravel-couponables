<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Exceptions;

class CouponExpiredException extends CouponException
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
