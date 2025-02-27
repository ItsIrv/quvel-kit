<?php

namespace Modules\Auth\Actions;

use App\Services\User\UserFindService;
use Illuminate\Http\JsonResponse;
use Modules\Auth\app\Http\Requests\LoginRequest;
use Modules\Auth\app\Services\UserAuthenticationService;
use Modules\Auth\Enums\LoginUserStatus;
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

        if (!$user = $this->userFindService->findByEmail($loginData['email'])) {
            throw new SignInUserException(LoginUserStatus::USER_NOT_FOUND->value);
        }

        if (!$user->hasVerifiedEmail()) {
            throw new SignInUserException(LoginUserStatus::EMAIL_NOT_VERIFIED->value);
        }

        if (
            !$this->UserAuthenticationService->attempt(
                $loginData['email'],
                $loginData['password'],
            )
        ) {
            throw new SignInUserException(LoginUserStatus::INVALID_CREDENTIALS->value);
        }

        return response()->json(
            ['message' => LoginUserStatus::SUCCESS->value],
            201,
        );
    }
}
