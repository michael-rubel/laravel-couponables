<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Exceptions;

class OverQuantityException extends CouponException
{
    /**
     * @var string
     */
    protected $message = 'The coupon is exhausted.';

    /**
     * @var int
     */
    protected $code = 400;
}
