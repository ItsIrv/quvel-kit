<?php

namespace Modules\Auth\Exceptions;

use App\Contracts\TranslatableEntity;
use App\Traits\RendersBadRequest;
use App\Traits\TranslatableException;
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
        int $code = 0,
    ) {
        parent::__construct($status->value, $code);
    }
}
