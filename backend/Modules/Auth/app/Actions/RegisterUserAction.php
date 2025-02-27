<?php

namespace Modules\Auth\Actions;

use App\Services\User\UserCreateService;
use App\Services\User\UserFindService;
use Illuminate\Http\JsonResponse;
use Modules\Auth\app\Http\Requests\LoginRequest;
use Modules\Auth\Enums\RegisterUserError;
use Modules\Auth\Exceptions\RegisterUserException;

class RegisterUserAction
{
    public function __construct(
        protected UserFindService $userFindService,
        protected UserCreateService $userCreateService,
    ) {
    }

    public function __invoke(LoginRequest $request): JsonResponse
    {
        $loginData = $request->validated();

        // Check if user already exists
        if ($this->userFindService->findByEmail($loginData['email'])) {
            throw new RegisterUserException(RegisterUserError::EMAIL_ALREADY_IN_USE->value);
        }

        $this->userCreateService->create($loginData);

        return response()->json(['message' => 'User registered successfully.'], 201);
    }
}
