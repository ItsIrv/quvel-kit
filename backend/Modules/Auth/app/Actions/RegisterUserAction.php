<?php

namespace Modules\Auth\Actions;

use App\Services\User\UserCreateService;
use App\Services\User\UserFindService;
use Illuminate\Http\JsonResponse;
use Modules\Auth\app\Http\Requests\LoginRequest;
use Modules\Auth\Enums\AuthStatusEnum;
use Modules\Auth\Exceptions\RegisterUserException;

/**
 * Action to register a new user.
 */
class RegisterUserAction
{
    /**
     * Create a new RegisterUserAction instance.
     *
     * @param UserFindService $userFindService
     * @param UserCreateService $userCreateService
     */
    public function __construct(
        protected UserFindService $userFindService,
        protected UserCreateService $userCreateService,
    ) {
    }

    /**
     * Register a new user.
     *
     * @param LoginRequest $request
     * @throws RegisterUserException
     */
    public function __invoke(LoginRequest $request): JsonResponse
    {
        assert(
            is_string(
                __(AuthStatusEnum::EMAIL_ALREADY_IN_USE->value),
            ),
        );

        assert(
            is_string(
                __(AuthStatusEnum::REGISTER_SUCCESS->value),
            ),
        );

        $loginData = $request->validated();

        // Check if user already exists
        if ($this->userFindService->findByEmail($loginData['email'])) {
            throw new RegisterUserException(
                __(AuthStatusEnum::EMAIL_ALREADY_IN_USE->value),
            );
        }

        $this->userCreateService->create($loginData);

        return response()->json(
            ['message' => __(AuthStatusEnum::REGISTER_SUCCESS->value)],
            201,
        );
    }
}
