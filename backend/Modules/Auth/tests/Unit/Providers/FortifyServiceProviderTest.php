<?php

namespace Modules\Auth\Tests\Unit\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Auth\Providers\FortifyServiceProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(FortifyServiceProvider::class)]
#[Group('auth-module')]
#[Group('auth-providers')]
class FortifyServiceProviderTest extends TestCase
{
    /**
     * Test that the service provider registers and configures Fortify correctly.
     */
    public function testFortifyIsConfiguredCorrectly(): void
    {
        // Create a new application instance with the service provider registered
        $app = $this->createApplication();
        $app->register(FortifyServiceProvider::class);

        // Since we can't directly access Fortify's private static properties,
        // we'll verify that the service provider was registered and booted
        $this->assertTrue($app->providerIsLoaded(FortifyServiceProvider::class));

        // We can also verify that the service provider is registered in the config
        $providers = $app->make('config')->get('app.providers', []);
        $this->assertContains(FortifyServiceProvider::class, $providers);
    }

    /**
     * Test that rate limiters are registered correctly.
     */
    public function testRateLimitersAreRegistered(): void
    {
        // Create a new application instance with the service provider registered
        $app = $this->createApplication();
        $app->register(FortifyServiceProvider::class);

        // Verify that the expected rate limiters are registered
        $expectedLimiters = [
            'login',
            'register',
            'verification.notice',
            'password.email',
            'password.update',
            'password.confirm',
            'user-password.update',
            'user-profile-information.update',
            'provider.redirect',
            'provider.callback',
            'provider.callback.post',
            'provider.create-nonce',
            'provider.redeem-nonce',
        ];

        foreach ($expectedLimiters as $limiterName) {
            $this->assertTrue(
                RateLimiter::limiter($limiterName) !== null,
                "Rate limiter '{$limiterName}' was not registered",
            );
        }
    }

    /**
     * Test that the login rate limiter works as expected.
     */
    public function testLoginRateLimiterReturnsExpectedLimit(): void
    {
        $this->app->register(FortifyServiceProvider::class);

        $request = new Request();
        $request->merge(['email' => 'Test@Example.com']);
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $callback = RateLimiter::limiter('login');
        $limit = $callback($request);

        $this->assertInstanceOf(Limit::class, $limit);
        $this->assertEquals(5, $limit->maxAttempts);
        $this->assertEquals(60, $limit->decaySeconds);
        $this->assertEquals('test@example.com|127.0.0.1', $limit->key);
    }

    /**
     * Test that the register rate limiter works as expected.
     */
    public function testRegisterRateLimiterReturnsExpectedLimit(): void
    {
        $this->app->register(FortifyServiceProvider::class);

        $request = new Request();
        $request->server->set('REMOTE_ADDR', '192.168.1.1');

        $callback = RateLimiter::limiter('register');
        $limit = $callback($request);

        $this->assertInstanceOf(Limit::class, $limit);
        $this->assertEquals(3, $limit->maxAttempts);
        $this->assertEquals(60, $limit->decaySeconds);
        $this->assertEquals('192.168.1.1', $limit->key);
    }

    /**
     * Test that the verification notice rate limiter works as expected.
     */
    public function testVerificationNoticeRateLimiterReturnsExpectedLimit(): void
    {
        $this->app->register(FortifyServiceProvider::class);

        // Test with email
        $request = new Request();
        $request->merge(['email' => 'verify@example.com']);
        $request->server->set('REMOTE_ADDR', '10.0.0.1');

        $callback = RateLimiter::limiter('verification.notice');
        $limit = $callback($request);

        $this->assertInstanceOf(Limit::class, $limit);
        $this->assertEquals(3, $limit->maxAttempts);
        $this->assertEquals(3600, $limit->decaySeconds); // 60 minutes * 60 seconds
        $this->assertEquals('verify@example.com', $limit->key);

        // Test without email (fallback to IP)
        $requestNoEmail = new Request();
        $requestNoEmail->server->set('REMOTE_ADDR', '10.0.0.2');

        $limitNoEmail = $callback($requestNoEmail);
        $this->assertEquals('10.0.0.2', $limitNoEmail->key);
    }

    /**
     * Test that the password email rate limiter works as expected.
     */
    public function testPasswordEmailRateLimiterReturnsExpectedLimit(): void
    {
        $this->app->register(FortifyServiceProvider::class);

        $request = new Request();
        $request->merge(['email' => 'reset@example.com']);
        $request->server->set('REMOTE_ADDR', '172.16.0.1');

        $callback = RateLimiter::limiter('password.email');
        $limit = $callback($request);

        $this->assertInstanceOf(Limit::class, $limit);
        $this->assertEquals(3, $limit->maxAttempts);
        $this->assertEquals(3600, $limit->decaySeconds);
        $this->assertEquals('reset@example.com|172.16.0.1', $limit->key);
    }

    /**
     * Test that the password update rate limiter works as expected.
     */
    public function testPasswordUpdateRateLimiterReturnsExpectedLimit(): void
    {
        $this->app->register(FortifyServiceProvider::class);

        $request = new Request();
        $request->server->set('REMOTE_ADDR', '203.0.113.1');

        $callback = RateLimiter::limiter('password.update');
        $limit = $callback($request);

        $this->assertInstanceOf(Limit::class, $limit);
        $this->assertEquals(5, $limit->maxAttempts);
        $this->assertEquals(60, $limit->decaySeconds);
        $this->assertEquals('203.0.113.1', $limit->key);
    }

    /**
     * Test that the password confirm rate limiter works as expected.
     */
    public function testPasswordConfirmRateLimiterReturnsExpectedLimit(): void
    {
        $this->app->register(FortifyServiceProvider::class);

        $request = new Request();
        $request->server->set('REMOTE_ADDR', '198.51.100.1');

        $callback = RateLimiter::limiter('password.confirm');
        $limit = $callback($request);

        $this->assertInstanceOf(Limit::class, $limit);
        $this->assertEquals(5, $limit->maxAttempts);
        $this->assertEquals(60, $limit->decaySeconds);
        $this->assertEquals('198.51.100.1', $limit->key);
    }

    /**
     * Test that the user password update rate limiter works as expected.
     */
    public function testUserPasswordUpdateRateLimiterReturnsExpectedLimit(): void
    {
        $this->app->register(FortifyServiceProvider::class);

        $request = new Request();
        $request->server->set('REMOTE_ADDR', '172.17.0.1');

        $callback = RateLimiter::limiter('user-password.update');
        $limit = $callback($request);

        $this->assertInstanceOf(Limit::class, $limit);
        $this->assertEquals(5, $limit->maxAttempts);
        $this->assertEquals(60, $limit->decaySeconds);
        $this->assertEquals('172.17.0.1', $limit->key);
    }

    /**
     * Test that the user profile information update rate limiter works as expected.
     */
    public function testUserProfileInformationUpdateRateLimiterReturnsExpectedLimit(): void
    {
        $this->app->register(FortifyServiceProvider::class);

        $request = new Request();
        $request->server->set('REMOTE_ADDR', '192.0.2.1');

        $callback = RateLimiter::limiter('user-profile-information.update');
        $limit = $callback($request);

        $this->assertInstanceOf(Limit::class, $limit);
        $this->assertEquals(3, $limit->maxAttempts);
        $this->assertEquals(60, $limit->decaySeconds);
        $this->assertEquals('192.0.2.1', $limit->key);
    }

    /**
     * Test that the provider redirect rate limiter works as expected.
     */
    public function testProviderRedirectRateLimiterReturnsExpectedLimit(): void
    {
        $this->app->register(FortifyServiceProvider::class);

        $request = new Request();
        $request->server->set('REMOTE_ADDR', '10.1.1.1');

        $callback = RateLimiter::limiter('provider.redirect');
        $limit = $callback($request);

        $this->assertInstanceOf(Limit::class, $limit);
        $this->assertEquals(10, $limit->maxAttempts);
        $this->assertEquals(60, $limit->decaySeconds);
        $this->assertEquals('10.1.1.1', $limit->key);
    }

    /**
     * Test that the provider callback rate limiter works as expected.
     */
    public function testProviderCallbackRateLimiterReturnsExpectedLimit(): void
    {
        $this->app->register(FortifyServiceProvider::class);

        $request = new Request();
        $request->server->set('REMOTE_ADDR', '172.20.0.1');

        $callback = RateLimiter::limiter('provider.callback');
        $limit = $callback($request);

        $this->assertInstanceOf(Limit::class, $limit);
        $this->assertEquals(10, $limit->maxAttempts);
        $this->assertEquals(60, $limit->decaySeconds);
        $this->assertEquals('172.20.0.1', $limit->key);
    }

    /**
     * Test that the provider callback post rate limiter works as expected.
     */
    public function testProviderCallbackPostRateLimiterReturnsExpectedLimit(): void
    {
        $this->app->register(FortifyServiceProvider::class);

        $request = new Request();
        $request->server->set('REMOTE_ADDR', '172.18.0.1');

        $callback = RateLimiter::limiter('provider.callback.post');
        $limit = $callback($request);

        $this->assertInstanceOf(Limit::class, $limit);
        $this->assertEquals(10, $limit->maxAttempts);
        $this->assertEquals(60, $limit->decaySeconds);
        $this->assertEquals('172.18.0.1', $limit->key);
    }

    /**
     * Test that the provider create nonce rate limiter works as expected.
     */
    public function testProviderCreateNonceRateLimiterReturnsExpectedLimit(): void
    {
        $this->app->register(FortifyServiceProvider::class);

        $request = new Request();
        $request->server->set('REMOTE_ADDR', '172.19.0.1');

        $callback = RateLimiter::limiter('provider.create-nonce');
        $limit = $callback($request);

        $this->assertInstanceOf(Limit::class, $limit);
        $this->assertEquals(5, $limit->maxAttempts);
        $this->assertEquals(60, $limit->decaySeconds);
        $this->assertEquals('172.19.0.1', $limit->key);
    }

    /**
     * Test that the provider redeem nonce rate limiter works as expected.
     */
    public function testProviderRedeemNonceRateLimiterReturnsExpectedLimit(): void
    {
        $this->app->register(FortifyServiceProvider::class);

        $request = new Request();
        $request->server->set('REMOTE_ADDR', '172.21.0.1');

        $callback = RateLimiter::limiter('provider.redeem-nonce');
        $limit = $callback($request);

        $this->assertInstanceOf(Limit::class, $limit);
        $this->assertEquals(5, $limit->maxAttempts);
        $this->assertEquals(60, $limit->decaySeconds);
        $this->assertEquals('172.21.0.1', $limit->key);
    }
}
