<?php

namespace Modules\Auth\Exceptions;

use App\Services\FrontendService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Modules\Auth\Enums\OAuthStatusEnum;
use Throwable;

/**
 * Exception to be thrown when OAuth related errors occur.
 */
class OAuthException extends Exception
{
    public function __construct(
        OAuthStatusEnum $status,
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
