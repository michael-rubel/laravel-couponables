<?php

namespace MichaelRubel\Couponables\Exceptions;

class OverQuantityException extends \Exception
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
