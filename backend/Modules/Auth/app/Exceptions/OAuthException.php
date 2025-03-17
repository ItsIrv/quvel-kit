<?php

namespace Modules\Auth\Exceptions;

use App\Contracts\TranslatableEntity;
use App\Services\FrontendService;
use App\Traits\TranslatableException;
use Exception;
use Illuminate\Http\RedirectResponse;
use Modules\Auth\Enums\OAuthStatusEnum;
use Throwable;

/**
 * Exception to be thrown when OAuth related errors occur.
 */
class OAuthException extends Exception implements TranslatableEntity
{
    use TranslatableException;

    public function __construct(
        private readonly OAuthStatusEnum $status,
        ?Throwable $previous = null
    ) {
        parent::__construct($status->value, 0, $previous);
    }

    public function render(): RedirectResponse
    {
        return app(FrontendService::class)->redirectPage(
            '',
            [
                'message' => $this->getMessage(),
            ]
        );
    }
}
