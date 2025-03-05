<?php

namespace Modules\Auth\Exceptions;

use App\Contracts\TranslatableException;
use App\Traits\HasTranslationKeyAsMessage;
use App\Traits\RendersBadRequest;
use Exception;
use Modules\Auth\Enums\OAuthStatusEnum;

/**
 * Exception to be thrown when OAuth related errors occur.
 */
class OAuthException extends Exception implements TranslatableException
{
    use RendersBadRequest;
    use HasTranslationKeyAsMessage;

    public function __construct(
        OAuthStatusEnum $status,
    ) {
        parent::__construct($status->value);
    }
}
