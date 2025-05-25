<?php

namespace Modules\Auth\Tests\Unit\Actions\User;

use Modules\Core\Http\Resources\UserResource;
use App\Models\User;
use Modules\Core\Services\User\UserFindService;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Mockery;
use Modules\Auth\Actions\User\LoginAction;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Auth\Enums\AuthStatusEnum;
use Modules\Auth\Exceptions\LoginActionException;
use Modules\Auth\Logs\Actions\User\LoginActionLogs;
use Modules\Auth\Services\UserAuthenticationService;
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

    private Mockery\MockInterface|LoginActionLogs $loginActionLogs;

    private LoginAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock dependencies
        $this->userFindService           = Mockery::mock(UserFindService::class);
        $this->userAuthenticationService = Mockery::mock(UserAuthenticationService::class);
        $this->responseFactory           = Mockery::mock(ResponseFactory::class);
        $this->loginActionLogs           = Mockery::mock(LoginActionLogs::class);

        // Set up common expectations for the logs
        $this->loginActionLogs->shouldReceive('loginSuccess')->zeroOrMoreTimes();
        $this->loginActionLogs->shouldReceive('loginFailedUserNotFound')->zeroOrMoreTimes();
        $this->loginActionLogs->shouldReceive('loginFailedInvalidCredentials')->zeroOrMoreTimes();
        $this->loginActionLogs->shouldReceive('loginFailedAccountInactive')->zeroOrMoreTimes();

        $this->action = new LoginAction(
            $this->userFindService,
            $this->userAuthenticationService,
            $this->responseFactory,
            $this->loginActionLogs,
        );
    }

    /**
     * Test successful login.
     */
    public function testSuccessfulLogin(): void
    {
        // Arrange
        $loginData = ['email' => 'test@example.com', 'password' => 'password'];
        $user      = Mockery::mock(User::class);

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

        // Add missing getAttribute for id
        $user->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn(1);

        $request = Mockery::mock(LoginRequest::class);
        $request->shouldReceive('validated')->once()->andReturn($loginData);
        $request->shouldReceive('ip')->zeroOrMoreTimes()->andReturn('127.0.0.1');
        $request->shouldReceive('userAgent')->zeroOrMoreTimes()->andReturn('PHPUnit Test');

        $this->userFindService->shouldReceive('findByEmail')
            ->with($loginData['email'])
            ->once()
            ->andReturn($user);

        $this->userAuthenticationService->shouldReceive('attempt')
            ->with($loginData['email'], $loginData['password'])
            ->once()
            ->andReturn(true);

        $expectedResponse = new JsonResponse([
            'message' => AuthStatusEnum::LOGIN_SUCCESS->value,
            'user'    => ['id' => 1, 'email' => 'test@example.com'],
        ], 201);

        $this->responseFactory->shouldReceive('json')
            ->once()
            ->with([
                'message' => AuthStatusEnum::LOGIN_SUCCESS->value,
                'user'    => new UserResource($user),
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
    public function testLoginFailsWhenUserNotFound(): void
    {
        // Arrange
        $loginData = ['email' => 'test@example.com', 'password' => 'password'];

        $request = Mockery::mock(LoginRequest::class);
        $request->shouldReceive('validated')->once()->andReturn($loginData);
        $request->shouldReceive('ip')->zeroOrMoreTimes()->andReturn('127.0.0.1');
        $request->shouldReceive('userAgent')->zeroOrMoreTimes()->andReturn('PHPUnit Test');

        $this->userFindService->shouldReceive('findByEmail')
            ->with($loginData['email'])
            ->once()
            ->andReturn(null);

        $this->expectException(LoginActionException::class);
        $this->expectExceptionMessage(AuthStatusEnum::USER_NOT_FOUND->value);

        // Act
        $this->action->__invoke($request);
    }

    /**
     * Test login fails when user registered via social login.
     */
    public function testLoginFailsWhenUserHasNoPassword(): void
    {
        // Arrange
        $loginData = ['email' => 'test@example.com', 'password' => 'password'];
        $user      = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')
            ->with('password')
            ->andReturn(null);

        $user->shouldReceive('getAttribute')
            ->with('provider_id')
            ->andReturn('google');

        $user->shouldReceive('hasVerifiedEmail')
            ->zeroOrMoreTimes()
            ->andReturn(true);

        $user->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn(1);

        $request = Mockery::mock(LoginRequest::class);
        $request->shouldReceive('validated')->once()->andReturn($loginData);
        $request->shouldReceive('ip')->zeroOrMoreTimes()->andReturn('127.0.0.1');
        $request->shouldReceive('userAgent')->zeroOrMoreTimes()->andReturn('PHPUnit Test');

        $this->userFindService->shouldReceive('findByEmail')
            ->with($loginData['email'])
            ->once()
            ->andReturn($user);

        $this->expectException(LoginActionException::class);
        $this->expectExceptionMessage(AuthStatusEnum::INVALID_CREDENTIALS->value);

        // Act
        $this->action->__invoke($request);
    }

    /**
     * Test login fails when incorrect password is provided.
     */
    public function testLoginFailsWhenPasswordIsIncorrect(): void
    {
        // Arrange
        $loginData = ['email' => 'test@example.com', 'password' => 'wrong-password'];
        $user      = Mockery::mock(User::class);

        $user->shouldReceive('getAttribute')
            ->with('provider_id')
            ->andReturn(null);

        $user->shouldReceive('getAttribute')
            ->with('password')
            ->andReturn('password');

        $user->shouldReceive('hasVerifiedEmail')
            ->andReturn(true);

        $user->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn(1);

        $request = Mockery::mock(LoginRequest::class);
        $request->shouldReceive('validated')->once()->andReturn($loginData);
        $request->shouldReceive('ip')->zeroOrMoreTimes()->andReturn('127.0.0.1');
        $request->shouldReceive('userAgent')->zeroOrMoreTimes()->andReturn('PHPUnit Test');

        $this->userFindService->shouldReceive('findByEmail')
            ->with($loginData['email'])
            ->once()
            ->andReturn($user);

        $this->userAuthenticationService->shouldReceive('attempt')
            ->with($loginData['email'], $loginData['password'])
            ->once()
            ->andReturn(false);

        $this->expectException(LoginActionException::class);
        $this->expectExceptionMessage(AuthStatusEnum::INVALID_CREDENTIALS->value);

        // Act
        $this->action->__invoke($request);
    }

    /**
     * Test login fails when user email is not verified.
     */
    public function testLoginFailsWhenEmailNotVerified(): void
    {
        // Arrange
        $loginData = ['email' => 'test@example.com', 'password' => 'password'];
        $user      = Mockery::mock(User::class);

        $user->shouldReceive('getAttribute')
            ->with('provider_id')
            ->andReturn(null);

        $user->shouldReceive('getAttribute')
            ->with('password')
            ->andReturn('password');

        $user->shouldReceive('hasVerifiedEmail')->andReturn(false);

        // Add missing getAttribute for id
        $user->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn(1);

        $request = Mockery::mock(LoginRequest::class);
        $request->shouldReceive('validated')->once()->andReturn($loginData);
        $request->shouldReceive('ip')->zeroOrMoreTimes()->andReturn('127.0.0.1');
        $request->shouldReceive('userAgent')->zeroOrMoreTimes()->andReturn('PHPUnit Test');

        $this->userFindService->shouldReceive('findByEmail')
            ->with($loginData['email'])
            ->once()
            ->andReturn($user);

        // We don't expect attempt to be called since the email verification check happens first
        // and throws an exception before we get to the authentication attempt

        $this->expectException(LoginActionException::class);
        $this->expectExceptionMessage(AuthStatusEnum::EMAIL_NOT_VERIFIED->value);

        // Act
        $this->action->__invoke($request);
    }
}
