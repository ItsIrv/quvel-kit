<?php

namespace Modules\Auth\Actions\Fortify;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Modules\Core\Services\FrontendService;

class PasswordViewRedirect
{
    public function __construct(
        protected FrontendService $frontendService,
    ) {
    }

    public function __invoke(string $token): RedirectResponse|Response
    {
        return $this->frontendService->redirect(
            '',
            [
                'form'  => 'password-reset',
                'token' => $token,
            ],
        );
    }
}
