<?php

namespace Modules\Auth\Actions\Fortify;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Modules\Auth\Enums\AuthStatusEnum;
use Modules\Core\Enums\StatusEnum;
use Modules\Core\Services\FrontendService;

class LoginViewRedirect
{
    public function __construct(
        private readonly FrontendService $frontendService,
    ) {
    }

    public function __invoke(): RedirectResponse|Response
    {
        return $this->frontendService->redirect('', ['message' => StatusEnum::INTERNAL_ERROR->value]);
    }
}
