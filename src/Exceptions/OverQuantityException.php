<?php

namespace MichaelRubel\Couponables\Exceptions;

class OverQuantityException extends \Exception
{
    /**
     * @var string
     */
    protected $message = 'This promotional code is reached its limit.';

    /**
     * @var int
     */
    protected $code = 400;
}
