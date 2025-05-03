<?php

namespace Modules\Core\Tests\Unit\Providers;

use App\Providers\AppServiceProvider;
use Modules\Core\Services\FrontendService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\URL;
use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Models\Tenant;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use Tests\TestCase;

#[CoversClass(className: AppServiceProvider::class)]
#[Group('core-module')]
#[Group('core-providers')]
class AppServiceProviderTest extends TestCase
{
    /**
     * Runs before each test.
     */
    #[Before]
    public function setupTest(): void
    {
        URL::spy();
    }

    /**
     * Ensures the register method binds correct services.
     */
    public function testRegisterMethodRuns(): void
    {
        $this->assertTrue(
            $this->app->bound(
                FrontendService::class,
            ),
        );
    }

    /**
     * Test that FrontendService is properly configured with the app URL from tenant config.
     *
     * @throws BindingResolutionException|Exception
     */
    public function testFrontendServiceGetsCorrectAppUrl(): void
    {
        // Mock the TenantContext class
        $mockTenantContext = $this->createMock(TenantContext::class);

        // Set up the mock to return a specific app URL
        $expectedAppUrl = $this->tenant->config->appUrl;
        $mockTenantContext->method('get')
            ->with()
            ->willReturn($this->tenant);

        // Replace the TenantContext in the container with our mock
        $this->app->instance(TenantContext::class, $mockTenantContext);

        // Resolve the FrontendService from the container
        $frontendService = $this->app->make(FrontendService::class);

        // Verify the FrontendService was created with the correct app URL
        // by testing one of its methods that uses the frontendUrl property
        $expectedFullUrl = "$expectedAppUrl/login";
        $this->assertEquals(
            $expectedFullUrl,
            $frontendService->getPageUrl('login'),
        );
    }

    /**
     * Test that FrontendService resolves as a scoped service, not a singleton.
     */
    public function testFrontendServiceIsScoped(): void
    {
        // Create two request contexts with different tenant configs
        $firstMockContext = $this->createMock(TenantContext::class);
        $firstMockContext->method('get')
            ->with()
            ->willReturn($this->tenant);

        $secondTenant = Tenant::find(1);

        $secondMockContext = $this->createMock(TenantContext::class);
        $secondMockContext->method('get')
            ->with()
            ->willReturn($secondTenant);

        // First request context
        $this->app->instance(TenantContext::class, $firstMockContext);
        $firstService = $this->app->make(FrontendService::class);

        // Flush the container to simulate a new request lifecycle
        $this->refreshApplication();

        // Second request context
        $this->app->instance(TenantContext::class, $secondMockContext);
        $secondService = $this->app->make(FrontendService::class);

        // The two services should be different instances with different URLs
        $this->assertNotSame($firstService, $secondService);
        $this->assertNotEquals(
            $firstService->getPageUrl('dashboard'),
            $secondService->getPageUrl('dashboard'),
        );
    }

    /**
     * Ensures boot forces HTTPS.
     */
    public function testBootForcesHttps(): void
    {
        URL::shouldReceive('forceScheme')
            ->once()
            ->with('https');

        $this->app->getProvider(AppServiceProvider::class)->boot();

        URL::shouldHaveReceived('forceScheme')
            ->once()
            ->with('https');
    }
}
