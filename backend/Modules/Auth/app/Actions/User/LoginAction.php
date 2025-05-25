<?php

namespace Modules\Auth\Actions\User;

use Modules\Core\Http\Resources\UserResource;
use Modules\Core\Services\User\UserFindService;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Auth\Enums\AuthStatusEnum;
use Modules\Auth\Exceptions\LoginActionException;
use Modules\Auth\Logs\Actions\User\LoginActionLogs;
use Modules\Auth\Services\UserAuthenticationService;

/**
 * Action to sign in a user.
 */
class LoginAction
{
    public function __construct(
        private readonly UserFindService $userFindService,
        private readonly UserAuthenticationService $userAuthenticationService,
        private readonly ResponseFactory $responseFactory,
        private readonly LoginActionLogs $logs,
    ) {
    }

    /**
     * Attempt to authenticate a user with email and password.
     *
     * @throws LoginActionException
     */
    public function __invoke(LoginRequest $request): JsonResponse
    {
        $loginData = $request->validated();

        // Find the user by email
        if (!$user = $this->userFindService->findByEmail($loginData['email'])) {
            $this->logs->loginFailedUserNotFound(
                $loginData['email'],
                $request->ip(),
                $request->userAgent(),
            );

            throw new LoginActionException(AuthStatusEnum::USER_NOT_FOUND);
        }

        // Check the user signed up with password
        if (!$user->password || $user->provider_id) {
            $this->logs->loginFailedInvalidCredentials(
                $loginData['email'],
                $request->ip(),
                $request->userAgent(),
            );

            throw new LoginActionException(AuthStatusEnum::INVALID_CREDENTIALS);
        }

        // Check if the user has verified their email if verify_email_before_login is true
        if (!$user->hasVerifiedEmail() && config('auth.verify_email_before_login')) {
            $this->logs->loginFailedAccountInactive(
                $loginData['email'],
                $user->id,
                $request->ip(),
                $request->userAgent(),
            );

            throw new LoginActionException(AuthStatusEnum::EMAIL_NOT_VERIFIED);
        }

        // Attempt to authenticate the user
        if (!$this->userAuthenticationService->attempt($loginData['email'], $loginData['password'])) {
            $this->logs->loginFailedInvalidCredentials(
                $loginData['email'],
                $request->ip(),
                $request->userAgent(),
            );

            throw new LoginActionException(AuthStatusEnum::INVALID_CREDENTIALS);
        }

        // Log successful login
        $this->logs->loginSuccess(
            $loginData['email'],
            $user->id,
            $request->ip(),
            $request->userAgent(),
        );

        return $this->responseFactory->json([
            'message' => AuthStatusEnum::LOGIN_SUCCESS->value,
            'user'    => new UserResource($user),
        ], 201);
    }
}
