<?php

namespace Modules\Auth\Actions\User;

use App\Services\User\UserCreateService;
use App\Services\User\UserFindService;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Modules\Auth\app\Http\Requests\RegisterRequest;
use Modules\Auth\Enums\AuthStatusEnum;
use Modules\Auth\Exceptions\RegisterUserException;

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
     * @throws RegisterUserException
     */
    public function __invoke(RegisterRequest $request): JsonResponse
    {
        $loginData = $request->validated();

        // Check if user already exists
        if ($this->userFindService->findByEmail($loginData['email'])) {
            throw new RegisterUserException(
                AuthStatusEnum::EMAIL_ALREADY_IN_USE,
            );
        }

        $this->userCreateService->create($loginData);

        return $this->responseFactory->json(
            ['message' => AuthStatusEnum::REGISTER_SUCCESS->getTranslatedMessage()],
            201,
        );
    }
}
