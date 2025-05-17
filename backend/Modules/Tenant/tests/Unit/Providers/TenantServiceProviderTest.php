<?php

namespace Modules\Tenant\Tests\Unit\Providers;

use Illuminate\Foundation\Application;
use Mockery;
use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Contracts\TenantResolver;
use Modules\Tenant\Providers\RouteServiceProvider;
use Modules\Tenant\Providers\TenantServiceProvider;
use Modules\Tenant\Services\FindService;
use Modules\Tenant\Services\RequestPrivacy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
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
            strtolower(FindService::class),
        ];

        $expectedRegisters = [
            strtolower(RouteServiceProvider::class),
        ];

        $expectedScoped = [
            strtolower(TenantContext::class),
            strtolower(RequestPrivacy::class),
            strtolower(TenantResolver::class),
        ];

        $this->assertEquals($expectedSingletons, $singletons);
        $this->assertEquals($expectedRegisters, $registers);
        $this->assertEquals($expectedScoped, $scoped);
    }
}
