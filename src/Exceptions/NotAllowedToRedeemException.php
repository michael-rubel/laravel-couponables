<?php

namespace MichaelRubel\Couponables\Exceptions;

class NotAllowedToRedeemException extends \Exception
{
    /**
     * @var string
     */
    protected $message = 'The coupon cannot be used by this model.';

    /**
     * @var int
     */
    protected $code = 403;
}
