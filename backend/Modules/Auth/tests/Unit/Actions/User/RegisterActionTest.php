<?php

namespace Modules\Auth\Tests\Unit\Actions\User;

use App\Services\User\UserCreateService;
use App\Services\User\UserFindService;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Mockery;
use Modules\Auth\Actions\User\RegisterAction;
use Modules\Auth\Enums\AuthStatusEnum;
use Modules\Auth\Exceptions\RegisterUserException;
use Modules\Auth\app\Http\Requests\RegisterRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(RegisterAction::class)]
#[Group('auth-module')]
#[Group('auth-actions')]
class RegisterActionTest extends TestCase
{
    private Mockery\MockInterface|UserFindService $userFindService;
    private Mockery\MockInterface|UserCreateService $userCreateService;
    private Mockery\MockInterface|ResponseFactory $responseFactory;
    private RegisterAction $action;

    public function setUp(): void
    {
        parent::setUp();

        // Mock dependencies
        $this->userFindService   = Mockery::mock(UserFindService::class);
        $this->userCreateService = Mockery::mock(UserCreateService::class);
        $this->responseFactory   = Mockery::mock(ResponseFactory::class);

        $this->action = new RegisterAction(
            $this->userFindService,
            $this->userCreateService,
            $this->responseFactory,
        );
    }

    /**
     * Test successful user registration.
     */
    public function testRegisterActionSuccessfullyCreatesUser(): void
    {
        // Arrange
        $registerData = ['email' => 'test@example.com', 'password' => 'password'];

        $request = Mockery::mock(RegisterRequest::class);
        $request->shouldReceive('validated')->once()->andReturn($registerData);

        $this->userFindService->shouldReceive('findByEmail')
            ->with($registerData['email'])
            ->once()
            ->andReturn(null);

        $this->userCreateService->shouldReceive('create')
            ->with($registerData)
            ->once();

        $expectedResponse = new JsonResponse([
            'message' => AuthStatusEnum::REGISTER_SUCCESS->getTranslatedMessage(),
        ], 201);

        $this->responseFactory->shouldReceive('json')
            ->once()
            ->with(['message' => AuthStatusEnum::REGISTER_SUCCESS->getTranslatedMessage()], 201)
            ->andReturn($expectedResponse);

        // Act
        $response = $this->action->__invoke($request);

        // Assert
        $this->assertSame($expectedResponse, $response);
    }

    /**
     * Test registration failure when email is already in use.
     */
    public function testRegisterActionFailsWhenEmailAlreadyExists(): void
    {
        // Arrange
        $registerData = ['email' => 'test@example.com', 'password' => 'password'];

        $request = Mockery::mock(RegisterRequest::class);
        $request->shouldReceive('validated')->once()->andReturn($registerData);

        $existingUser = Mockery::mock(\App\Models\User::class);

        $this->userFindService->shouldReceive('findByEmail')
            ->with($registerData['email'])
            ->once()
            ->andReturn($existingUser); // Return a User mock instead of stdClass

        $this->expectException(RegisterUserException::class);
        $this->expectExceptionMessage(AuthStatusEnum::EMAIL_ALREADY_IN_USE->value);

        // Act
        $this->action->__invoke($request);
    }
}
