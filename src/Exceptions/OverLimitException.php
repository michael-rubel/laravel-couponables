<?php

namespace MichaelRubel\Couponables\Exceptions;

class OverLimitException extends \Exception
{
    /**
     * @var string
     */
    protected $message = 'Promotional code is already used by current model.';

    /**
     * @var int
     */
    protected $code = 403;
}
