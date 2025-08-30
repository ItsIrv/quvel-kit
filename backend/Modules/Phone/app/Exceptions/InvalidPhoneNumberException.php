<?php

namespace Modules\Phone\Exceptions;

use Exception;

/**
 * Exception thrown when a phone number is invalid.
 */
class InvalidPhoneNumberException extends Exception
{
    public function __construct(string $message = 'Invalid phone number format', int $code = 422)
    {
        parent::__construct($message, $code);
    }
}
