<?php

namespace Modules\Phone\Exceptions;

use Exception;

/**
 * Exception thrown when an OTP has expired or is invalid.
 */
class OtpExpiredException extends Exception
{
    public function __construct(string $message = 'Verification code has expired or is invalid', int $code = 422)
    {
        parent::__construct($message, $code);
    }
}
