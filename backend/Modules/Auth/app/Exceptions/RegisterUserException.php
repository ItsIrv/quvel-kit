<?php

namespace Modules\Auth\Exceptions;

use App\Traits\RendersBadRequest;
use Exception;
use Modules\Auth\Enums\AuthStatusEnum;

/**
 * Exception to be thrown when the user registration fails.
 */
class RegisterUserException extends Exception
{
    use RendersBadRequest;

    public function __construct(
        private readonly AuthStatusEnum $status,
    ) {
        parent::__construct($status->value);
    }
}
