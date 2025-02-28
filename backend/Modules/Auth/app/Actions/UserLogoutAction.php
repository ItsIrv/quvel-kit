<?php

namespace Modules\Auth\Actions;

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
        assert(
            is_string(
                __(AuthStatusEnum::LOGOUT_SUCCESS->value),
            ),
        );

        $this->userAuthenticationService->logout();

        return response()->json(
            ['message' => __(AuthStatusEnum::LOGOUT_SUCCESS->value)],
        );
    }
}
