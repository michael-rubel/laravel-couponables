<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Exceptions;

class OverLimitException extends CouponException
{
    /**
     * @var string
     */
    protected $message = 'Coupon usage limit has been reached.';

    /**
     * @var int
     */
    protected $code = 403;
}
