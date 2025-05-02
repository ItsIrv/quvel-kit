<?php

namespace Modules\Auth\Exceptions;

use Modules\Core\Contracts\TranslatableEntity;
use App\Traits\RendersBadRequest;
use App\Traits\TranslatableException;
use Exception;
use Modules\Auth\Enums\AuthStatusEnum;

/**
 * Exception to be thrown when the user registration fails.
 */
class LoginActionException extends Exception implements TranslatableEntity
{
    use RendersBadRequest;
    use TranslatableException;

    public function __construct(
        AuthStatusEnum $status,
    ) {
        parent::__construct($status->value);
    }
}
