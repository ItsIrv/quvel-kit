<?php

namespace Modules\Auth\Exceptions;

use App\Traits\RendersBadRequest;
use Exception;

/**
 * Exception to be thrown when OAuth related errors occur.
 */
class OAuthException extends Exception
{
    use RendersBadRequest;
}
