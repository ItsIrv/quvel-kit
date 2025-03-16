<?php

namespace Modules\Auth\Actions\User;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Modules\Auth\app\Services\UserAuthenticationService;
use Modules\Auth\Enums\AuthStatusEnum;

/**
 * Action to logout a user.
 */
class LogoutAction
{
    /**
     * Create a new LogoutAction instance.
     */
    public function __construct(
        private readonly UserAuthenticationService $userAuthenticationService,
        private readonly ResponseFactory $responseFactory,
    ) {}

    /**
     * Logout the current user.
     */
    public function __invoke(): JsonResponse
    {
        $this->userAuthenticationService->logout();

        return $this->responseFactory->json([
            'message' => AuthStatusEnum::LOGOUT_SUCCESS->getTranslatedMessage(),
        ]);
    }
}
