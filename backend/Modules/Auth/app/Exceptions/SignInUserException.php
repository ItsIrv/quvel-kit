<?php

namespace Modules\Auth\Exceptions;

use App\Traits\RendersBadRequest;
use Exception;

/**
 * Exception to be thrown when the user registration fails.
 */
class SignInUserException extends Exception
{
    use RendersBadRequest;
}
