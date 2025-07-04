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
use Laravel\Fortify\Events\TwoFactorAuthenticationChallenged;

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
        $email     = (string) $loginData['email'];
        $password  = (string) $loginData['password'];

        // Find the user by email
        $user = $this->userFindService->findByEmail($email);
        if ($user === null) {
            $this->logs->loginFailedUserNotFound(
                $email,
                $request->ip() ?? 'unknown',
                $request->userAgent(),
            );

            throw new LoginActionException(AuthStatusEnum::USER_NOT_FOUND);
        }

        // Check the user signed up with password
        if ($user->password === null || $user->provider_id !== null) {
            $this->logs->loginFailedInvalidCredentials(
                $email,
                $request->ip() ?? 'unknown',
                $request->userAgent(),
            );

            throw new LoginActionException(AuthStatusEnum::INVALID_CREDENTIALS);
        }

        // Check if the user has verified their email if verify_email_before_login is true
        if (!$user->hasVerifiedEmail() && ((bool) config('auth.verify_email_before_login'))) {
            $this->logs->loginFailedAccountInactive(
                $email,
                $user->id,
                $request->ip() ?? 'unknown',
                $request->userAgent(),
            );

            throw new LoginActionException(AuthStatusEnum::EMAIL_NOT_VERIFIED);
        }

        // Validate credentials first
        if (!$this->userAuthenticationService->validateCredentials($email, $password)) {
            $this->logs->loginFailedInvalidCredentials(
                $email,
                $request->ip() ?? 'unknown',
                $request->userAgent(),
            );

            throw new LoginActionException(AuthStatusEnum::INVALID_CREDENTIALS);
        }

        // Check if user has two-factor authentication enabled
        if ($user->hasEnabledTwoFactorAuthentication()) {
            // Log successful credential validation but pending 2FA (don't log in yet)
            $this->logs->loginSuccess(
                $email,
                $user->id,
                $request->ip() ?? 'unknown',
                $request->userAgent(),
            );

            // Set session state for two-factor challenge (same as Fortify)
            $request->session()->put([
                'login.id'       => $user->getKey(),
                'login.remember' => $request->boolean('remember'),
            ]);

            // Dispatch Fortify's event
            TwoFactorAuthenticationChallenged::dispatch($user);

            // Return the two-factor challenge response (without logging in)
            return $this->responseFactory->json([
                'two_factor' => true,
            ], 200);
        }

        // No 2FA required - proceed with normal login
        $this->userAuthenticationService->logInWithId($user->id);

        // Log successful login
        $this->logs->loginSuccess(
            $email,
            $user->id,
            $request->ip() ?? 'unknown',
            $request->userAgent(),
        );

        return $this->responseFactory->json([
            'message' => AuthStatusEnum::LOGIN_SUCCESS->value,
            'user'    => new UserResource($user),
        ], 201);
    }
}
