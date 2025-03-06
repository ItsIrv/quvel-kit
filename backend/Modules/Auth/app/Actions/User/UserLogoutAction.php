<?php

namespace Modules\Auth\Actions\User;

use Illuminate\Http\JsonResponse;
use Modules\Auth\app\Services\UserAuthenticationService;
use Modules\Auth\Enums\AuthStatusEnum;

/**
 * Action to logout a user.
 */
class UserLogoutAction
{
    /**
     * Create a new UserLogoutAction instance.
     * @param UserAuthenticationService $userAuthenticationService
     */
    public function __construct(
        private readonly UserAuthenticationService $userAuthenticationService,
    ) {
    }

    /**
     * Logout the current user.
     * @return JsonResponse
     */
    public function __invoke(): JsonResponse
    {
        $this->userAuthenticationService->logout();

        return response()->json(
            ['message' => AuthStatusEnum::LOGOUT_SUCCESS->getTranslatedMessage()],
        );
    }
}
