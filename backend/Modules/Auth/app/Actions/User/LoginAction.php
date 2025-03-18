<?php

namespace Modules\Auth\Actions\User;

use App\Services\User\UserFindService;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Modules\Auth\app\Http\Requests\LoginRequest;
use Modules\Auth\Enums\AuthStatusEnum;
use Modules\Auth\Exceptions\LoginActionException;
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
    ) {}

    /**
     * Attempt to authenticate a user with email and password.
     *
     * @throws LoginActionException
     */
    public function __invoke(LoginRequest $request): JsonResponse
    {
        $loginData = $request->validated();

        // Find the user by email
        if (! $user = $this->userFindService->findByEmail($loginData['email'])) {
            throw new LoginActionException(AuthStatusEnum::USER_NOT_FOUND);
        }

        // Check the user signed up with password
        if (! $user->password || $user->provider_id) {
            throw new LoginActionException(AuthStatusEnum::INVALID_CREDENTIALS);
        }

        // Attempt to authenticate the user
        if (! $this->userAuthenticationService->attempt($loginData['email'], $loginData['password'])) {
            throw new LoginActionException(AuthStatusEnum::INVALID_CREDENTIALS);
        }

        // Check if the user has verified their email
        if (! $user->hasVerifiedEmail()) {
            throw new LoginActionException(AuthStatusEnum::EMAIL_NOT_VERIFIED);
        }

        return $this->responseFactory->json([
            'message' => AuthStatusEnum::LOGIN_SUCCESS->getTranslatedMessage(),
            'user' => $user,
        ], 201);
    }
}
