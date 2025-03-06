<?php

namespace Modules\Auth\Exceptions;

use App\Contracts\TranslatableEntity;
use App\Traits\TranslatableException;
use App\Traits\RendersBadRequest;
use Exception;
use Modules\Auth\Enums\AuthStatusEnum;

/**
 * Exception to be thrown when the user registration fails.
 */
class RegisterUserException extends Exception implements TranslatableEntity
{
    use RendersBadRequest;
    use TranslatableException;

    public function __construct(
        AuthStatusEnum $status,
    ) {
        parent::__construct($status->value);
    }
}
