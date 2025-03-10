<?php

namespace Modules\Auth\Actions\User;

use App\Services\User\UserFindService;
use Illuminate\Http\JsonResponse;
use Modules\Auth\app\Http\Requests\LoginRequest;
use Modules\Auth\app\Services\UserAuthenticationService;
use Modules\Auth\Enums\AuthStatusEnum;
use Modules\Auth\Exceptions\SignInUserException;

/**
 * Action to sign in a user.
 */
class LoginUserAction
{
    /**
     * Create a new LoginUserAction instance.
     * @param \App\Services\User\UserFindService $userFindService
     * @param \Modules\Auth\app\Services\UserAuthenticationService $UserAuthenticationService
     */
    public function __construct(
        protected UserFindService $userFindService,
        protected UserAuthenticationService $UserAuthenticationService,
    ) {
    }

    /**
     * Attempt to authenticate a user with email and password.
     *
     * @param LoginRequest $request
     * @throws SignInUserException
     */
    public function __invoke(LoginRequest $request): JsonResponse
    {
        $loginData = $request->validated();

        // Find the user by email
        if (!$user = $this->userFindService->findByEmail($loginData['email'])) {
            throw new SignInUserException(
                AuthStatusEnum::USER_NOT_FOUND,
            );
        }

        // Check the user signed up with password
        if (!$user->password || $user->provider_id) {
            throw new SignInUserException(
                AuthStatusEnum::INVALID_CREDENTIALS,
            );
        }

        // Attempt to authenticate the user
        if (
            !$this->UserAuthenticationService->attempt(
                $loginData['email'],
                $loginData['password'],
            )
        ) {
            throw new SignInUserException(
                AuthStatusEnum::INVALID_CREDENTIALS,
            );
        }

        // Check if the user has verified their email
        if (!$user->hasVerifiedEmail()) {
            throw new SignInUserException(
                AuthStatusEnum::EMAIL_NOT_VERIFIED,
            );
        }

        return response()->json(
            [
                'message' => AuthStatusEnum::LOGIN_SUCCESS->getTranslatedMessage(),
                'user'    => $user,
            ],
            201,
        );
    }
}
