<?php

namespace Modules\Auth\Tests\Unit\Providers;

use Illuminate\Foundation\Application;
use Modules\Auth\Providers\AuthServiceProvider;
use Modules\Auth\Providers\EventServiceProvider;
use Modules\Auth\Providers\RouteServiceProvider;
use Modules\Auth\Services\ClientNonceService;
use Modules\Auth\Services\HmacService;
use Modules\Auth\Services\NonceSessionService;
use Modules\Auth\Services\ServerTokenService;
use Modules\Auth\Services\SocialiteService;
use Modules\Auth\Services\UserAuthenticationService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(AuthServiceProvider::class)]
#[Group('auth-module')]
#[Group('auth-providers')]
class AuthServiceProviderTest extends TestCase
{
    /**
     * Test that the service provider registers services correctly.
     */
    public function test_registers_services(): void
    {
        $app = $this->createMock(Application::class);

        $singletons = [];
        $registers = [];

        $app->method('singleton')
            ->willReturnCallback(function ($class) use (&$singletons): void {
                $singletons[] = strtolower($class);
            });

        $app->method('scoped')
            ->willReturnCallback(function ($class) use (&$singletons): void {
                $singletons[] = strtolower($class);
            });

        $app->method('register')
            ->willReturnCallback(function ($class) use (&$registers): void {
                $registers[] = strtolower($class);
            });

        $provider = new AuthServiceProvider($app);
        $provider->register();

        $expectedSingletons = [
            strtolower(HmacService::class),
            strtolower(ClientNonceService::class),
            strtolower(ServerTokenService::class),
            strtolower(UserAuthenticationService::class),
            strtolower(NonceSessionService::class),
            strtolower(SocialiteService::class),
        ];

        $expectedRegisters = [
            strtolower(EventServiceProvider::class),
            strtolower(RouteServiceProvider::class),
        ];

        $this->assertEqualsCanonicalizing($expectedSingletons, $singletons);
        $this->assertEqualsCanonicalizing($expectedRegisters, $registers);
    }
}
