<?php

namespace Modules\Auth\Actions\User;

use App\Http\Resources\UserResource;
use Modules\Core\Services\User\UserCreateService;
use Modules\Core\Services\User\UserFindService;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Http\Requests\RegisterRequest;
use Modules\Auth\Enums\AuthStatusEnum;
use Modules\Auth\Services\UserAuthenticationService;
use Modules\Auth\Exceptions\RegisterActionException;

/**
 * Action to register a new user.
 */
class RegisterAction
{
    /**
     * Create a new RegisterAction instance.
     */
    public function __construct(
        private readonly UserFindService $userFindService,
        private readonly UserCreateService $userCreateService,
        private readonly ResponseFactory $responseFactory,
        private readonly UserAuthenticationService $userAuthenticationService,
    ) {
    }

    /**
     * Register a new user.
     *
     * @throws RegisterActionException
     */
    public function __invoke(RegisterRequest $request): JsonResponse
    {
        $registerData = $request->validated();

        // Check if user already exists
        if ($this->userFindService->findByEmail($registerData['email'])) {
            throw new RegisterActionException(
                AuthStatusEnum::EMAIL_ALREADY_IN_USE,
            );
        }

        $user = $this->userCreateService->create($registerData);

        // Log the user in after sign up like fortify does if verify_email_before_login is false
        if (config('auth.verify_email_before_login') === false) {
            $this->userAuthenticationService->logInWithId($user->id);

            return $this->responseFactory->json(
                [
                    'status' => AuthStatusEnum::LOGIN_SUCCESS->value,
                    'user'   => new UserResource($user),
                ],
                200,
            );
        }

        return $this->responseFactory->json(
            [
                'status' => AuthStatusEnum::REGISTER_SUCCESS->value,
            ],
            201,
        );
    }
}
