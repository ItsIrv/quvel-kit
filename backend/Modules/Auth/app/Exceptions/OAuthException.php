<?php

namespace Modules\Auth\Exceptions;

use App\Traits\HasTranslationKeyAsMessage;
use App\Traits\RendersBadRequest;
use Exception;
use Modules\Auth\Enums\OAuthStatusEnum;

/**
 * Exception to be thrown when OAuth related errors occur.
 */
class OAuthException extends Exception
{
    use RendersBadRequest;
    use HasTranslationKeyAsMessage;

    public function __construct(
        private readonly OAuthStatusEnum $status,
    ) {
        parent::__construct($status->value);
    }
}
