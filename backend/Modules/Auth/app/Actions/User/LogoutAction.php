<?php

namespace Modules\Auth\Actions\User;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Enums\AuthStatusEnum;
use Modules\Auth\Services\UserAuthenticationService;
use Modules\Auth\Logs\Actions\User\LogoutActionLogs;

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
        private readonly LogoutActionLogs $logs,
    ) {
    }

    /**
     * Logout the current user.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user   = $request->user();
        $userId = $user?->id;

        $this->userAuthenticationService->logout();

        $this->logs->logoutSuccess(
            $userId,
            $request->ip() ?? 'unknown',
            $request->userAgent(),
        );

        return $this->responseFactory->json([
            'message' => AuthStatusEnum::LOGOUT_SUCCESS->value,
        ]);
    }
}
