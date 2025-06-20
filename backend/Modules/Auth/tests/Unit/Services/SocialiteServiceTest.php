<?php

namespace Modules\Auth\Tests\Unit\Services;

use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\SocialiteManager;
use Laravel\Socialite\Two\AbstractProvider;
use Mockery;
use Mockery\MockInterface;
use Modules\Auth\Exceptions\OAuthException;
use Modules\Auth\Services\SocialiteService;
use Modules\Core\Services\FrontendService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(SocialiteService::class)]
#[Group('auth-module')]
#[Group('auth-services')]
class SocialiteServiceTest extends TestCase
{
    private MockInterface|SocialiteManager $socialiteManager;
    private MockInterface|FrontendService $frontendService;

    private SocialiteService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock dependencies
        $this->socialiteManager = Mockery::mock(SocialiteManager::class);
        $this->frontendService = Mockery::mock(FrontendService::class);
        
        // Mock the app() call to return our mocked FrontendService
        $this->app->instance(FrontendService::class, $this->frontendService);

        // Initialize the service
        $this->service = new SocialiteService(
            $this->socialiteManager
        );
    }

    public function testGetRedirectResponseReturnsUrlWithoutServerToken(): void
    {
        $redirectUrl = Mockery::mock(RedirectResponse::class);
        $driver = Mockery::mock(AbstractProvider::class);
        $provider = 'google';

        // Mock FrontendService behavior
        $this->frontendService->shouldReceive('getPageUrl')
            ->with("/auth/provider/$provider/callback")
            ->andReturn('https://app.example.com/auth/provider/google/callback');

        // Mock driver behavior
        $this->socialiteManager->shouldReceive('buildProvider')->andReturn($driver);
        $driver->shouldReceive('redirect')->once()->andReturn($redirectUrl);

        // Act
        $result = $this->service->getRedirectResponse($provider);

        // Assert
        $this->assertSame($redirectUrl, $result);
    }

    public function testGetRedirectResponseReturnsUrlWithServerToken(): void
    {
        $redirectUrl = Mockery::mock(RedirectResponse::class);
        $driver = Mockery::mock(AbstractProvider::class);
        $provider = 'google';
        $signedServerToken = 'test_token';

        // Mock FrontendService behavior
        $this->frontendService->shouldReceive('getPageUrl')
            ->with("/auth/provider/$provider/callback")
            ->andReturn('https://app.example.com/auth/provider/google/callback');

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
    public function testGetProviderUserReturnsStatelessUser(): void
    {
        $provider = 'google';
        $mockUser = Mockery::mock(SocialiteUser::class);
        $driver = Mockery::mock(AbstractProvider::class);

        // Mock FrontendService behavior
        $this->frontendService->shouldReceive('getPageUrl')
            ->with("/auth/provider/$provider/callback")
            ->andReturn('https://app.example.com/auth/provider/google/callback');

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
    public function testGetProviderUserReturnsStatefulUser(): void
    {
        $provider = 'google';
        $mockUser = Mockery::mock(SocialiteUser::class);
        $driver = Mockery::mock(AbstractProvider::class);

        // Mock FrontendService behavior
        $this->frontendService->shouldReceive('getPageUrl')
            ->with("/auth/provider/$provider/callback")
            ->andReturn('https://app.example.com/auth/provider/google/callback');

        // Mock driver stateful user fetch
        $this->socialiteManager->shouldReceive('buildProvider')->andReturn($driver);
        $driver->shouldReceive('user')->once()->andReturn($mockUser);

        // Act
        $result = $this->service->getProviderUser($provider, false);

        // Assert
        $this->assertSame($mockUser, $result);
    }

    public function testGetRedirectUriUsesCorrectFrontendService(): void
    {
        $provider = 'github';
        $expectedUrl = 'https://app.example.com/auth/provider/github/callback';
        
        // Mock FrontendService to return specific URL
        $this->frontendService->shouldReceive('getPageUrl')
            ->with("/auth/provider/$provider/callback")
            ->once()
            ->andReturn($expectedUrl);

        $driver = Mockery::mock(AbstractProvider::class);
        $redirectResponse = Mockery::mock(RedirectResponse::class);

        // The buildProvider method should be called with config including our redirect URI
        $this->socialiteManager->shouldReceive('buildProvider')
            ->with(
                Mockery::any(), 
                Mockery::on(function ($config) use ($expectedUrl) {
                    return isset($config['redirect']) && $config['redirect'] === $expectedUrl;
                })
            )
            ->andReturn($driver);

        $driver->shouldReceive('redirect')->andReturn($redirectResponse);

        // Act
        $result = $this->service->getRedirectResponse($provider);

        // Assert
        $this->assertSame($redirectResponse, $result);
    }
}
