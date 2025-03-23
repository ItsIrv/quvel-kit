<?php

namespace Modules\Auth\Tests\Unit\Services;

use Exception;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\SocialiteManager;
use Laravel\Socialite\Two\AbstractProvider;
use Mockery;
use Mockery\MockInterface;
use Modules\Auth\Enums\OAuthStatusEnum;
use Modules\Auth\Exceptions\OAuthException;
use Modules\Auth\Services\SocialiteService;
use Modules\Tenant\database\factories\TenantConfigFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(SocialiteService::class)]
#[Group('auth-module')]
#[Group('auth-services')]
class SocialiteServiceTest extends TestCase
{
    private MockInterface|SocialiteManager $socialiteManager;

    private SocialiteService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $tenantConfig = TenantConfigFactory::create(
            apiDomain: 'api.quvel.app',
            internalApiDomain: 'internal-api.quvel.app',
            toArray: false
        );

        // Mock dependencies
        $this->seedMock();
        $this->socialiteManager = Mockery::mock(SocialiteManager::class);
        $this->tenantContextMock->shouldReceive('getConfig')
            ->andReturn($tenantConfig);

        // Initialize the service
        $this->service = new SocialiteService(
            $this->socialiteManager,
            $this->tenantContextMock
        );
    }

    public function test_get_redirect_response_returns_url_without_server_token(): void
    {
        $redirectUrl = Mockery::mock(RedirectResponse::class);
        $driver = Mockery::mock(AbstractProvider::class);
        $provider = 'google';

        // Mock driver behavior
        $this->socialiteManager->shouldReceive('buildProvider')->andReturn($driver);
        $driver->shouldReceive('redirect')->once()->andReturn($redirectUrl);

        // Act
        $result = $this->service->getRedirectResponse($provider);

        // Assert
        $this->assertSame($redirectUrl, $result);
    }

    public function test_get_redirect_response_returns_url_with_server_token(): void
    {
        $redirectUrl = Mockery::mock(RedirectResponse::class);
        $driver = Mockery::mock(AbstractProvider::class);
        $provider = 'google';
        $signedServerToken = 'test_token';

        // Mock driver behavior
        $this->socialiteManager->shouldReceive('buildProvider')->andReturn($driver);
        $driver->shouldReceive('stateless')->andReturnSelf(); // Mock stateless method.
        $driver->shouldReceive('with')->with(['state' => $signedServerToken])->andReturnSelf(); // Mock with method.
        $driver->shouldReceive('redirect')->once()->andReturn($redirectUrl);

        // Act
        $result = $this->service->getRedirectResponse($provider, $signedServerToken);

        // Assert
        $this->assertSame($redirectUrl, $result);
    }

    /**
     * @throws OAuthException
     */
    public function test_get_provider_user_returns_stateless_user(): void
    {
        $provider = 'google';
        $mockUser = Mockery::mock(SocialiteUser::class);
        $driver = Mockery::mock(AbstractProvider::class);

        // Mock driver stateless user fetch
        $this->socialiteManager->shouldReceive('buildProvider')->andReturn($driver);
        $driver->shouldReceive('stateless')->andReturn($driver);
        $driver->shouldReceive('user')->once()->andReturn($mockUser);

        // Act
        $result = $this->service->getProviderUser($provider, true);

        // Assert
        $this->assertSame($mockUser, $result);
    }

    /**
     * @throws OAuthException
     */
    public function test_get_provider_user_returns_stateful_user(): void
    {
        $provider = 'google';
        $mockUser = Mockery::mock(SocialiteUser::class);
        $driver = Mockery::mock(AbstractProvider::class);

        // Mock driver stateful user fetch
        $this->socialiteManager->shouldReceive('buildProvider')->andReturn($driver);
        $driver->shouldReceive('user')->once()->andReturn($mockUser);

        // Act
        $result = $this->service->getProviderUser($provider, false);

        // Assert
        $this->assertSame($mockUser, $result);
    }
}
