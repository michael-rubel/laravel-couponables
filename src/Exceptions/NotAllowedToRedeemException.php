<?php

namespace MichaelRubel\Couponables\Exceptions;

class NotAllowedToRedeemException extends \Exception
{
    /**
     * @var string
     */
    protected $message = 'You cannot use this coupon.';

    /**
     * @var int
     */
    protected $code = 403;
}
