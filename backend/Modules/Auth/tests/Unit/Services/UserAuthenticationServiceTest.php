<?php

namespace Modules\Auth\Tests\Unit\Services;

use App\Models\User;
use App\Services\User\UserCreateService;
use App\Services\User\UserFindService;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\Guard;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Mockery;
use Mockery\MockInterface;
use Modules\Auth\Enums\OAuthStatusEnum;
use Modules\Auth\Exceptions\OAuthException;
use Modules\Auth\app\Services\UserAuthenticationService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(UserAuthenticationService::class)]
#[Group('auth-module')]
#[Group('auth-services')]
class UserAuthenticationServiceTest extends TestCase
{
    private AuthFactory|MockInterface $auth;
    private UserFindService|MockInterface $userFindService;
    private UserCreateService|MockInterface $userCreateService;
    private UserAuthenticationService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->auth = Mockery::mock(AuthFactory::class);
        $this->userFindService = Mockery::mock(UserFindService::class);
        $this->userCreateService = Mockery::mock(UserCreateService::class);

        $this->service = new UserAuthenticationService(
            $this->auth,
            $this->userFindService,
            $this->userCreateService
        );
    }

    public function testAttemptSuccessful(): void
    {
        $guard = Mockery::mock(Guard::class);
        $email = 'test@example.com';
        $password = 'password';

        $this->auth->shouldReceive('guard')
            ->andReturn($guard);

        $guard->shouldReceive('attempt')
            ->once()
            ->with(['email' => $email, 'password' => $password])
            ->andReturn(true);

        $result = $this->service->attempt($email, $password);

        $this->assertTrue($result);
    }

    public function testAttemptFailure(): void
    {
        $guard = Mockery::mock(Guard::class);
        $email = 'test@example.com';
        $password = 'password';

        $this->auth->shouldReceive('guard')
            ->andReturn($guard);

        $guard->shouldReceive('attempt')
            ->once()
            ->with(['email' => $email, 'password' => $password])
            ->andReturn(false);

        $result = $this->service->attempt($email, $password);

        $this->assertFalse($result);
    }

    public function testLogout(): void
    {
        $guard = Mockery::mock(Guard::class);

        $this->auth->shouldReceive('guard')
            ->andReturn($guard);

        $guard->shouldReceive('logout')
            ->once();

        $this->service->logout();

        $this->assertTrue(true);
    }

    public function testLogInWithId(): void
    {
        $guard = Mockery::mock(Guard::class);
        $userId = 1;
        $user = Mockery::mock(User::class);

        $this->auth->shouldReceive('guard')
            ->andReturn($guard);

        $guard->shouldReceive('loginUsingId')
            ->once()
            ->with($userId)
            ->andReturn($user);

        $result = $this->service->logInWithId($userId);

        $this->assertSame($user, $result);
    }

    /**
     * @throws OAuthException
     */
    public function testHandleOAuthLoginExistingUser(): void
    {
        $provider = 'google';
        $providerUser = Mockery::mock(SocialiteUser::class);
        $user = Mockery::mock(User::class);
        $email = 'test@example.com';
        $providerId = 'google_123456';

        $providerUser->shouldReceive('getId')
            ->andReturn('123456');
        $providerUser->shouldReceive('getEmail')
            ->andReturn($email);

        $this->userFindService->shouldReceive('findByEmail')
            ->with($email)
            ->andReturn($user);

        $user->shouldReceive('getAttribute')
            ->with('provider_id')
            ->andReturn($providerId);

        $user->shouldReceive('getAttribute')
            ->with('email_verified_at')
            ->andReturn('2023-01-01 00:00:00');

        [$resultUser, $status] = $this->service->handleOAuthLogin($provider, $providerUser);

        $this->assertSame($user, $resultUser);
        $this->assertEquals(OAuthStatusEnum::LOGIN_OK, $status);
    }

    /**
     * @throws OAuthException
     */
    public function testHandleOAuthLoginNewUser(): void
    {
        $provider = 'google';
        $providerUser = Mockery::mock(SocialiteUser::class);
        $user = Mockery::mock(User::class);
        $email = 'test@example.com';
        $providerId = 'google_123456';
        $name = 'Test User';
        $avatar = 'https://example.com/avatar.jpg';

        $providerUser->shouldReceive('getId')
            ->andReturn('123456');
        $providerUser->shouldReceive('getEmail')
            ->andReturn($email);
        $providerUser->shouldReceive('getName')
            ->andReturn($name);
        $providerUser->shouldReceive('getAvatar')
            ->andReturn($avatar);

        $this->userFindService->shouldReceive('findByEmail')
            ->with($email)
            ->andReturn(null);

        $this->userCreateService->shouldReceive('create')
            ->with([
                'email' => $email,
                'provider_id' => $providerId,
                'name' => $name,
                'avatar' => $avatar,
                'password' => null,
            ])
            ->andReturn($user);

        [$resultUser, $status] = $this->service->handleOAuthLogin($provider, $providerUser);

        $this->assertSame($user, $resultUser);
        $this->assertEquals(OAuthStatusEnum::USER_CREATED, $status);
    }

    public function testHandleOAuthLoginEmailNotVerified(): void
    {
        $provider = 'google';
        $providerUser = Mockery::mock(SocialiteUser::class);
        $user = Mockery::mock(User::class);
        $email = 'test@example.com';
        $providerId = 'google_123456';

        $providerUser->shouldReceive('getId')
            ->andReturn('123456');
        $providerUser->shouldReceive('getEmail')
            ->andReturn($email);

        $this->userFindService->shouldReceive('findByEmail')
            ->with($email)
            ->andReturn($user);

        $user->shouldReceive('getAttribute')
            ->with('provider_id')
            ->andReturn($providerId);

        $user->shouldReceive('getAttribute')
            ->with('email_verified_at')
            ->andReturn(null);

        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage(OAuthStatusEnum::EMAIL_NOT_VERIFIED->value);

        $this->service->handleOAuthLogin($provider, $providerUser);
    }

    public function testHandleOAuthLoginThrowsEmailTakenException(): void
    {
        $provider = 'google';
        $providerUser = Mockery::mock(SocialiteUser::class);
        $user = Mockery::mock(User::class);
        $email = 'test@example.com';
        $differentProviderId = 'facebook_654321';

        $providerUser->shouldReceive('getId')
            ->andReturn('123456');
        $providerUser->shouldReceive('getEmail')
            ->andReturn($email);

        $this->userFindService->shouldReceive('findByEmail')
            ->with($email)
            ->andReturn($user);

        $user->shouldReceive('getAttribute')
            ->with('provider_id')
            ->andReturn($differentProviderId);

        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage(OAuthStatusEnum::EMAIL_TAKEN->value);

        $this->service->handleOAuthLogin($provider, $providerUser);
    }
}
