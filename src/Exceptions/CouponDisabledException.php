<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Exceptions;

class CouponDisabledException extends CouponException
{
    /**
     * @var string
     */
    protected $message = 'The coupon is disabled.';

    /**
     * @var int
     */
    protected $code = 400;
}
