<?php

namespace Tests\Unit\Services;

use App\Services\FrontendService;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Mockery;
use Modules\Tenant\ValueObjects\TenantConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(FrontendService::class)]
#[Group('frontend')]
#[Group('services')]
class FrontendServiceTest extends TestCase
{
    private FrontendService $frontendService;

    private TenantConfig $mockConfig;

    private Redirector $mockRedirector;

    private ResponseFactory $mockResponseFactory;

    private string $baseUrl = 'https://quvel.127.0.0.1.nip.io';

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockConfig = TenantConfig::fromArray([
            'app_url' => $this->baseUrl,
            'capacitor_scheme' => null,
            'api_url' => 'https://api.local',
            'app_name' => 'Test App',
            'app_env' => 'local',
            'debug' => false,
            'mail_from_name' => 'Quvel',
            'mail_from_address' => 'test@quvel.app',
        ]);

        $this->mockRedirector = Mockery::mock(Redirector::class);
        $this->mockResponseFactory = Mockery::mock(ResponseFactory::class);

        $mockRequest = Mockery::mock(Request::class);
        $mockRequest->shouldReceive('hasHeader')->with('X-Capacitor')->andReturn(false);

        $this->frontendService = new FrontendService(
            config: $this->mockConfig,
            redirector: $this->mockRedirector,
            request: $mockRequest,
            responseFactory: $this->mockResponseFactory,
        );
    }

    /**
     * Test redirect to a frontend route for normal requests.
     */
    public function test_redirect(): void
    {
        $path = '/dashboard';
        $expectedUrl = "$this->baseUrl$path";

        $this->mockRedirector
            ->shouldReceive('away')
            ->once()
            ->with($expectedUrl)
            ->andReturn(new RedirectResponse($expectedUrl));

        $response = $this->frontendService->redirect($path);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals($expectedUrl, $response->getTargetUrl());
    }

    /**
     * Test redirect to a frontend page with query parameters for normal requests.
     */
    public function test_redirect_with_query_parameters(): void
    {
        $path = '/profile';
        $params = ['id' => 42, 'mode' => 'edit'];
        $expectedUrl = "$this->baseUrl$path?".http_build_query($params);

        $this->mockRedirector
            ->shouldReceive('away')
            ->once()
            ->with($expectedUrl)
            ->andReturn(new RedirectResponse($expectedUrl));

        $response = $this->frontendService->redirect($path, $params);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals($expectedUrl, $response->getTargetUrl());
    }

    /**
     * Test redirect with capacitor scheme `_deep`.
     */
    public function test_redirect_with_capacitor_deep_scheme(): void
    {
        $path = '/settings';
        $params = ['setting' => 'dark'];
        $expectedUrl = "$this->baseUrl$path?".http_build_query($params);

        $mockRequest = Mockery::mock(Request::class);
        $mockRequest->shouldReceive('hasHeader')->with('X-Capacitor')->andReturn(true);

        $config = TenantConfig::fromArray([
            ...$this->mockConfig->toArray(),
            'capacitor_scheme' => '_deep',
        ]);

        $frontendService = new FrontendService(
            config: $config,
            redirector: $this->mockRedirector,
            request: $mockRequest,
            responseFactory: $this->mockResponseFactory
        );

        $this->mockRedirector
            ->shouldReceive('away')
            ->once()
            ->with($expectedUrl)
            ->andReturn(new RedirectResponse($expectedUrl));

        $response = $frontendService->redirect($path, $params);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals($expectedUrl, $response->getTargetUrl());
    }

    public function test_redirect_with_custom_capacitor_scheme(): void
    {
        $path = '/dashboard';
        $params = ['user' => '123'];
        $customScheme = 'mycapacitor';
        $customUrl = "$this->baseUrl$path?".http_build_query($params);
        $expectedUrl = preg_replace('/^https?/', $customScheme, $customUrl);

        $mockRequest = Mockery::mock(Request::class);
        $mockRequest->shouldReceive('hasHeader')->with('X-Capacitor')->andReturn(true);

        $config = TenantConfig::fromArray([
            ...$this->mockConfig->toArray(),
            'capacitor_scheme' => $customScheme,
        ]);

        $frontendService = new FrontendService(
            config: $config,
            redirector: $this->mockRedirector,
            request: $mockRequest,
            responseFactory: $this->mockResponseFactory
        );

        $this->mockResponseFactory
            ->shouldReceive('view')
            ->once()
            ->with('redirect', [
                'message' => null,
                'schemeUrl' => $expectedUrl,
            ])
            ->andReturn(new RedirectResponse($expectedUrl));

        $response = $frontendService->redirect($path, $params);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals($expectedUrl, $response->getTargetUrl());
    }

    /**
     * Test generating a URL without redirecting.
     */
    public function test_get_page_url(): void
    {
        $path = '/settings';
        $params = ['theme' => 'dark'];
        $expectedUrl = "$this->baseUrl$path?".http_build_query($params);

        $url = $this->frontendService->getPageUrl($path, $params);

        $this->assertEquals($expectedUrl, $url);
    }
}
