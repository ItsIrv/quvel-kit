<?php

namespace Modules\Auth\Tests\Unit\Actions\User;

use App\Models\User;
use Modules\Core\Services\User\UserCreateService;
use Modules\Core\Services\User\UserFindService;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Mockery;
use Modules\Auth\Actions\User\RegisterAction;
use Modules\Auth\Http\Requests\RegisterRequest;
use Modules\Auth\Enums\AuthStatusEnum;
use Modules\Auth\Exceptions\RegisterActionException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;
use Modules\Auth\Services\UserAuthenticationService;

#[CoversClass(RegisterAction::class)]
#[Group('auth-module')]
#[Group('auth-actions')]
class RegisterActionTest extends TestCase
{
    private Mockery\MockInterface|UserFindService $userFindService;

    private Mockery\MockInterface|UserCreateService $userCreateService;

    private Mockery\MockInterface|ResponseFactory $responseFactory;

    private Mockery\MockInterface|UserAuthenticationService $userAuthenticationService;

    private RegisterAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock dependencies
        $this->userFindService           = Mockery::mock(UserFindService::class);
        $this->userCreateService         = Mockery::mock(UserCreateService::class);
        $this->responseFactory           = Mockery::mock(ResponseFactory::class);
        $this->userAuthenticationService = Mockery::mock(UserAuthenticationService::class);

        $this->action = new RegisterAction(
            $this->userFindService,
            $this->userCreateService,
            $this->responseFactory,
            $this->userAuthenticationService,
        );
    }

    /**
     * Test successful user registration.
     */
    public function testRegisterActionSuccessfullyCreatesUser(): void
    {
        config(['auth.verify_email_before_login' => false]);

        $registerData = ['email' => 'test@example.com', 'password' => 'password'];

        $request = Mockery::mock(RegisterRequest::class);
        $request->shouldReceive('validated')->once()->andReturn($registerData);

        $this->userFindService
            ->shouldReceive('findByEmail')
            ->with($registerData['email'])
            ->once()
            ->andReturn(null);

        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')->with('id')->andReturn(1); // used by logInWithId

        $this->userCreateService
            ->shouldReceive('create')
            ->with($registerData)
            ->once()
            ->andReturn($user);

        $this->userAuthenticationService
            ->shouldReceive('logInWithId')
            ->with(1)
            ->once();

        $this->responseFactory
            ->shouldReceive('json')
            ->once()
            ->with(Mockery::on(function ($payload) use ($user) {
                return $payload['status'] === AuthStatusEnum::LOGIN_SUCCESS->value
                    && $payload['user'] instanceof \Modules\Core\Http\Resources\UserResource;
            }), 200)
            ->andReturn(new JsonResponse(['status' => AuthStatusEnum::LOGIN_SUCCESS->value], 200));

        $response = $this->action->__invoke($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->status());
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

        $existingUser = Mockery::mock(User::class);

        $this->userFindService->shouldReceive('findByEmail')
            ->with($registerData['email'])
            ->once()
            ->andReturn($existingUser); // Return a User mock instead of stdClass

        $this->expectException(RegisterActionException::class);
        $this->expectExceptionMessage(AuthStatusEnum::EMAIL_ALREADY_IN_USE->value);

        // Act
        $this->action->__invoke($request);
    }

    public function testRegisterActionReturns201WhenEmailVerificationIsRequired(): void
    {
        config(['auth.verify_email_before_login' => true]);

        $registerData = ['email' => 'verify@example.com', 'password' => 'secure'];

        $request = Mockery::mock(RegisterRequest::class);
        $request->shouldReceive('validated')->once()->andReturn($registerData);

        $this->userFindService
            ->shouldReceive('findByEmail')
            ->with($registerData['email'])
            ->once()
            ->andReturn(null);

        $user = Mockery::mock(User::class);

        $this->userCreateService
            ->shouldReceive('create')
            ->with($registerData)
            ->once()
            ->andReturn($user);

        // Should NOT log the user in if verification is required
        $this->userAuthenticationService
            ->shouldNotReceive('logInWithId');

        $this->responseFactory
            ->shouldReceive('json')
            ->once()
            ->with([
                'status' => AuthStatusEnum::REGISTER_SUCCESS->value,
            ], 201)
            ->andReturn(new JsonResponse([
                'status' => AuthStatusEnum::REGISTER_SUCCESS->value,
            ], 201));

        $response = $this->action->__invoke($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->status());
    }
}
