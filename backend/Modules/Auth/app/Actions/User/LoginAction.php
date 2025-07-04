<?php

namespace Modules\Auth\Actions\User;

use Modules\Core\Http\Resources\UserResource;
use Modules\Core\Services\User\UserFindService;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\RedirectsIfTwoFactorAuthenticatable;
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
        private readonly RedirectsIfTwoFactorAuthenticatable $redirectsIfTwoFactorAuthenticatable,
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

        // Attempt to authenticate the user
        if (!$this->userAuthenticationService->attempt($email, $password)) {
            $this->logs->loginFailedInvalidCredentials(
                $email,
                $request->ip() ?? 'unknown',
                $request->userAgent(),
            );

            throw new LoginActionException(AuthStatusEnum::INVALID_CREDENTIALS);
        }

        // Check if user has two-factor authentication enabled
        if (
            $user->two_factor_secret &&
            $user->hasEnabledTwoFactorAuthentication()
        ) {
            // Log successful credential validation but pending 2FA
            $this->logs->loginSuccess(
                $email,
                $user->id,
                $request->ip() ?? 'unknown',
                $request->userAgent(),
            );

            // Return the two-factor challenge response
            return $this->redirectsIfTwoFactorAuthenticatable->handle($request, function () use ($user) {
                return $this->responseFactory->json([
                    'message' => AuthStatusEnum::LOGIN_SUCCESS->value,
                    'user'    => new UserResource($user),
                ], 201);
            });
        }

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
