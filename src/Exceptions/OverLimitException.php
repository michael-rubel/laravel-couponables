<?php

namespace MichaelRubel\Couponables\Exceptions;

class OverLimitException extends \Exception
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
