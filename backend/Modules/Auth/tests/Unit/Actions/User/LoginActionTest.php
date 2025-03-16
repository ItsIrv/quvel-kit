<?php

namespace Modules\Auth\Tests\Unit\Actions\User;

use App\Models\User;
use App\Services\User\UserFindService;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Mockery;
use Modules\Auth\Actions\User\LoginAction;
use Modules\Auth\app\Http\Requests\LoginRequest;
use Modules\Auth\app\Services\UserAuthenticationService;
use Modules\Auth\Enums\AuthStatusEnum;
use Modules\Auth\Exceptions\SignInUserException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(LoginAction::class)]
#[Group('auth-module')]
#[Group('auth-actions')]
class LoginActionTest extends TestCase
{
    private Mockery\MockInterface|UserFindService $userFindService;

    private Mockery\MockInterface|UserAuthenticationService $userAuthenticationService;

    private Mockery\MockInterface|ResponseFactory $responseFactory;

    private LoginAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock dependencies
        $this->userFindService = Mockery::mock(UserFindService::class);
        $this->userAuthenticationService = Mockery::mock(UserAuthenticationService::class);
        $this->responseFactory = Mockery::mock(ResponseFactory::class);

        $this->action = new LoginAction(
            $this->userFindService,
            $this->userAuthenticationService,
            $this->responseFactory,
        );
    }

    /**
     * Test successful login.
     */
    public function test_successful_login(): void
    {
        // Arrange
        $loginData = ['email' => 'test@example.com', 'password' => 'password'];
        $user = Mockery::mock(User::class);

        $user->shouldReceive('getAttribute')
            ->with('password')
            ->andReturn('password');

        $user->shouldReceive('getAttribute')
            ->with('provider_id')
            ->andReturn(null);

        $user->shouldReceive('hasVerifiedEmail')
            ->andReturn(true);

        $user->shouldReceive('jsonSerialize')
            ->andReturn(['id' => 1, 'email' => 'test@example.com']);

        $request = Mockery::mock(LoginRequest::class);
        $request->shouldReceive('validated')->once()->andReturn($loginData);

        $this->userFindService->shouldReceive('findByEmail')
            ->with($loginData['email'])
            ->once()
            ->andReturn($user);

        $this->userAuthenticationService->shouldReceive('attempt')
            ->with($loginData['email'], $loginData['password'])
            ->once()
            ->andReturn(true);

        $expectedResponse = new JsonResponse([
            'message' => AuthStatusEnum::LOGIN_SUCCESS->getTranslatedMessage(),
            'user' => ['id' => 1, 'email' => 'test@example.com'],
        ], 201);

        $this->responseFactory->shouldReceive('json')
            ->once()
            ->with([
                'message' => AuthStatusEnum::LOGIN_SUCCESS->getTranslatedMessage(),
                'user' => $user,
            ], 201)
            ->andReturn($expectedResponse);

        // Act
        $response = $this->action->__invoke($request);

        // Assert
        $this->assertSame($expectedResponse, $response);
    }

    /**
     * Test login fails when the user is not found.
     */
    public function test_login_fails_when_user_not_found(): void
    {
        // Arrange
        $loginData = ['email' => 'test@example.com', 'password' => 'password'];

        $request = Mockery::mock(LoginRequest::class);
        $request->shouldReceive('validated')->once()->andReturn($loginData);

        $this->userFindService->shouldReceive('findByEmail')
            ->with($loginData['email'])
            ->once()
            ->andReturn(null);

        $this->expectException(SignInUserException::class);
        $this->expectExceptionMessage(AuthStatusEnum::USER_NOT_FOUND->value);

        // Act
        $this->action->__invoke($request);
    }

    /**
     * Test login fails when user registered via social login.
     */
    public function test_login_fails_when_user_has_no_password(): void
    {
        // Arrange
        $loginData = ['email' => 'test@example.com', 'password' => 'password'];
        $user = Mockery::mock(User::class);
        $user->shouldReceive('password')->andReturn(null);
        $user->shouldReceive('provider_id')->andReturn('google');
        $user->shouldReceive('getAttribute')->andReturn('hashed-password');

        $request = Mockery::mock(LoginRequest::class);
        $request->shouldReceive('validated')->once()->andReturn($loginData);

        $this->userFindService->shouldReceive('findByEmail')
            ->with($loginData['email'])
            ->once()
            ->andReturn($user);

        $this->expectException(SignInUserException::class);
        $this->expectExceptionMessage(AuthStatusEnum::INVALID_CREDENTIALS->value);

        // Act
        $this->action->__invoke($request);
    }

    /**
     * Test login fails when incorrect password is provided.
     */
    public function test_login_fails_when_password_is_incorrect(): void
    {
        // Arrange
        $loginData = ['email' => 'test@example.com', 'password' => 'wrong-password'];
        $user = Mockery::mock(User::class);

        $user->shouldReceive('getAttribute')
            ->with('provider_id')
            ->andReturn(null);

        $user->shouldReceive('getAttribute')
            ->with('password')
            ->andReturn('password');

        $request = Mockery::mock(LoginRequest::class);
        $request->shouldReceive('validated')->once()->andReturn($loginData);

        $this->userFindService->shouldReceive('findByEmail')
            ->with($loginData['email'])
            ->once()
            ->andReturn($user);

        $this->userAuthenticationService->shouldReceive('attempt')
            ->with($loginData['email'], $loginData['password'])
            ->once()
            ->andReturn(false);

        $this->expectException(SignInUserException::class);
        $this->expectExceptionMessage(AuthStatusEnum::INVALID_CREDENTIALS->value);

        // Act
        $this->action->__invoke($request);
    }

    /**
     * Test login fails when user email is not verified.
     */
    public function test_login_fails_when_email_not_verified(): void
    {
        // Arrange
        $loginData = ['email' => 'test@example.com', 'password' => 'password'];
        $user = Mockery::mock(User::class);

        $user->shouldReceive('getAttribute')
            ->with('provider_id')
            ->andReturn(null);

        $user->shouldReceive('getAttribute')
            ->with('password')
            ->andReturn('password');

        $user->shouldReceive('hasVerifiedEmail')->andReturn(false);
        $user->shouldReceive('getAttribute')->andReturn('hashed-password');

        $request = Mockery::mock(LoginRequest::class);
        $request->shouldReceive('validated')->once()->andReturn($loginData);

        $this->userFindService->shouldReceive('findByEmail')->once()->andReturn($user);
        $this->userAuthenticationService->shouldReceive('attempt')->once()->andReturn(true);

        $this->expectException(SignInUserException::class);
        $this->expectExceptionMessage(AuthStatusEnum::EMAIL_NOT_VERIFIED->value);

        // Act
        $this->action->__invoke($request);
    }
}
