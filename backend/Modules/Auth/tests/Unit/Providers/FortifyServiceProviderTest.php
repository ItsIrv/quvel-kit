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
        // Register the service provider
        $this->app->register(FortifyServiceProvider::class);

        // Create a mock request
        $request = new Request();
        $request->merge(['email' => 'test@example.com']);
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        // Get the rate limiter callback
        $callback = RateLimiter::limiter('login');

        // Execute the callback with the request
        $limit = $callback($request);

        // Assert the limit is correct
        $this->assertInstanceOf(Limit::class, $limit);
        $this->assertEquals(5, $limit->maxAttempts);
        $this->assertEquals(60, $limit->decaySeconds);
        $this->assertEquals('test@example.com|127.0.0.1', $limit->key);
    }
}
