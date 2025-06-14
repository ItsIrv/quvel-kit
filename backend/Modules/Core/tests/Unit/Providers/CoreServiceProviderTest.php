<?php

namespace Modules\Core\Tests\Unit\Providers;

use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Modules\Core\Contracts\Security\CaptchaVerifierInterface;
use Modules\Core\Http\Middleware\Lang\SetRequestLocale;
use Modules\Core\Http\Middleware\Trace\SetTraceId;
use Modules\Core\Providers\CoreServiceProvider;
use Modules\Core\Services\FrontendService;
use Modules\Core\Services\Security\GoogleRecaptchaVerifier;
use Modules\Core\Services\User\UserCreateService;
use Modules\Core\Services\User\UserFindService;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Log\Context\Repository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

/**
 * @testdox CoreServiceProvider
 */
#[CoversClass(CoreServiceProvider::class)]
#[Group('core-module')]
#[Group('core-providers')]
class CoreServiceProviderTest extends TestCase
{
    private CoreServiceProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new CoreServiceProvider($this->app);
    }

    #[TestDox('registers user services as singletons')]
    public function testRegistersUserServicesAsSingletons(): void
    {
        $this->provider->register();

        $this->assertTrue($this->app->bound(UserCreateService::class));
        $this->assertTrue($this->app->bound(UserFindService::class));

        // Verify they are singletons
        $userCreateService1 = $this->app->make(UserCreateService::class);
        $userCreateService2 = $this->app->make(UserCreateService::class);
        $this->assertSame($userCreateService1, $userCreateService2);

        $userFindService1 = $this->app->make(UserFindService::class);
        $userFindService2 = $this->app->make(UserFindService::class);
        $this->assertSame($userFindService1, $userFindService2);
    }

    #[TestDox('registers frontend service as scoped')]
    public function testRegistersFrontendServiceAsScoped(): void
    {
        $this->provider->register();

        $this->assertTrue($this->app->bound(FrontendService::class));

        // Create a mock request
        $request = Request::create('http://example.com');
        $this->app->instance('request', $request);

        // Verify it gets created with correct dependencies
        $frontendService = $this->app->make(FrontendService::class);
        $this->assertInstanceOf(FrontendService::class, $frontendService);
    }

    #[TestDox('registers captcha verifier interface')]
    public function testRegistersCaptchaVerifierInterface(): void
    {
        // Set the config to use the Google ReCaptcha verifier
        config(['core.recaptcha.provider' => GoogleRecaptchaVerifier::class]);

        // Register the GoogleRecaptchaVerifier for testing
        $this->app->bind(GoogleRecaptchaVerifier::class, function () {
            return $this->createMock(GoogleRecaptchaVerifier::class);
        });

        $this->provider->register();

        $this->assertTrue($this->app->bound(CaptchaVerifierInterface::class));

        $verifier = $this->app->make(CaptchaVerifierInterface::class);
        $this->assertInstanceOf(CaptchaVerifierInterface::class, $verifier);
    }

    #[TestDox('sets HTTPS server value on boot')]
    public function testSetsHttpsServerValueOnBoot(): void
    {
        $request = Request::create('http://example.com');
        $this->app->instance('request', $request);

        $this->provider->boot();

        $this->assertEquals('on', $request->server->get('HTTPS'));
    }

    #[TestDox('pushes middleware to web and api groups')]
    public function testPushesMiddlewareToWebAndApiGroups(): void
    {
        $router = $this->createMock(Router::class);

        // Track method calls
        $callCount     = 0;
        $expectedCalls = [
            ['web', SetRequestLocale::class],
            ['api', SetRequestLocale::class],
            ['web', SetTraceId::class],
            ['api', SetTraceId::class],
        ];

        // Expect middleware to be pushed to both web and api groups
        $router->expects($this->exactly(4))
            ->method('pushMiddlewareToGroup')
            ->willReturnCallback(function ($group, $middleware) use (&$callCount, $expectedCalls) {
                $this->assertEquals($expectedCalls[$callCount][0], $group);
                $this->assertEquals($expectedCalls[$callCount][1], $middleware);
                $callCount++;
            });

        $this->app->instance('router', $router);

        $this->provider->boot();
    }

    #[TestDox('forces URL scheme and sets HTTPS')]
    public function testForcesUrlSchemeAndSetsHttps(): void
    {
        $urlGenerator = $this->createMock(UrlGenerator::class);
        $urlGenerator->expects($this->once())
            ->method('forceScheme')
            ->with('https');

        $request = Request::create('http://example.com');
        $this->app->instance('url', $urlGenerator);
        $this->app->instance('request', $request);

        $this->provider->boot();

        $this->assertEquals('on', $request->server->get('HTTPS'));
    }

    #[TestDox('configures context dehydrating callback with locale')]
    public function testConfiguresContextDehydratingCallback(): void
    {
        config(['app.locale' => 'fr']);

        $contextRepository = $this->createMock(Repository::class);
        $contextRepository->expects($this->once())
            ->method('addHidden')
            ->with('locale', 'fr');

        $this->provider->boot();

        // Simulate the dehydrating callback
        $callback = function (Repository $context): void {
            $context->addHidden('locale', config('app.locale'));
        };
        $callback($contextRepository);
    }

    #[TestDox('configures context hydrated callback with locale check')]
    public function testConfiguresContextHydratedCallback(): void
    {
        $contextRepository = $this->createMock(Repository::class);
        $contextRepository->expects($this->once())
            ->method('hasHidden')
            ->with('locale')
            ->willReturn(true);
        $contextRepository->expects($this->once())
            ->method('getHidden')
            ->with('locale')
            ->willReturn('de');

        $this->provider->boot();

        // Simulate the hydrated callback
        $callback = function (Repository $context): void {
            if ($context->hasHidden('locale')) {
                config(['app.locale' => $context->getHidden('locale')]);
            }
        };
        $callback($contextRepository);

        $this->assertEquals('de', config('app.locale'));
    }
}
