<?php

namespace Modules\Tenant\Tests\Unit\Providers;

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Foundation\Application;
use Mockery;
use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Providers\RouteServiceProvider;
use Modules\Tenant\Providers\TenantServiceProvider;
use Modules\Tenant\Services\TenantConfigApplier;
use Modules\Tenant\Services\TenantFindService;
use Modules\Tenant\Services\TenantResolverService;
use Modules\Tenant\Services\TenantSessionService;
use Modules\Tenant\ValueObjects\TenantConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;
use Tests\TestCase;

#[CoversClass(TenantServiceProvider::class)]
#[Group('tenant-module')]
#[Group('tenant-providers')]
class TenantServiceProviderTest extends TestCase
{
    /**
     * Test that the service provider registers services correctly.
     */
    public function testRegistersServices(): void
    {
        $app = $this->createMock(
            Application::class,
        );

        $singletons = [];
        $registers  = [];
        $scoped     = [];

        $app->method('singleton')
            ->willReturnCallback(function ($class) use (&$singletons): void {
                $singletons[] = strtolower($class);
            });

        $app->method('register')
            ->willReturnCallback(function ($class) use (&$registers): void {
                $registers[] = strtolower($class);
            });

        $app->method('scoped')
            ->willReturnCallback(function ($class) use (&$scoped): void {
                $scoped[] = strtolower($class);
            });

        $tenantContext = Mockery::mock(TenantContext::class);
        $tenantContext->shouldReceive('get')
            ->andReturn($this->tenant);

        $app->method('rebinding')
            ->with(
                $this->equalTo('request'),
                $this->callback(function ($callback) use ($app, $tenantContext): bool {
                    $app->method('make')
                        ->with($this->equalTo(TenantContext::class))
                        ->willReturn($tenantContext);

                    $callback($app);
                    return true;
                }),
            );

        $provider = new TenantServiceProvider($app);
        $provider->register();

        $expectedSingletons = [
            strtolower(TenantSessionService::class),
            strtolower(TenantFindService::class),
            strtolower(TenantResolverService::class),
        ];

        $expectedRegisters = [
            strtolower(RouteServiceProvider::class),
        ];

        $expectedScoped = [
            strtolower(TenantContext::class),
        ];

        $this->assertEquals($expectedSingletons, $singletons);
        $this->assertEquals($expectedRegisters, $registers);
        $this->assertEquals($expectedScoped, $scoped);
    }
}
