<?php

namespace Modules\Auth\Exceptions;

use App\Contracts\TranslatableEntity;
use App\Traits\TranslatableException;
use App\Traits\RendersBadRequest;
use Exception;
use Modules\Auth\Enums\OAuthStatusEnum;

/**
 * Exception to be thrown when OAuth related errors occur.
 */
class OAuthException extends Exception implements TranslatableEntity
{
    use RendersBadRequest;
    use TranslatableException;

    public function __construct(
        OAuthStatusEnum $status,
    ) {
        parent::__construct($status->value);
    }
}
