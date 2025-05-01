<?php

namespace Modules\Auth\Actions\User;

use App\Services\User\UserCreateService;
use App\Services\User\UserFindService;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Http\Requests\RegisterRequest;
use Modules\Auth\Enums\AuthStatusEnum;
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

        $this->userCreateService->create($registerData);

        return $this->responseFactory->json(
            ['message' => AuthStatusEnum::REGISTER_SUCCESS->value],
            201,
        );
    }
}
