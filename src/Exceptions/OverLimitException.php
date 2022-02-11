<?php

namespace MichaelRubel\Couponables\Exceptions;

class OverLimitException extends \Exception
{
    /**
     * @var string
     */
    protected $message = 'The user is already reached its limit of coupons.';

    /**
     * @var int
     */
    protected $code = 403;
}
