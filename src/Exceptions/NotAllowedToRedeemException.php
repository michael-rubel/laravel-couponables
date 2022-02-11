<?php

namespace MichaelRubel\Couponables\Exceptions;

class NotAllowedToRedeemException extends \Exception
{
    /**
     * @var string
     */
    protected $message = 'The promotional code cannot be used by this model.';

    /**
     * @var int
     */
    protected $code = 403;
}
