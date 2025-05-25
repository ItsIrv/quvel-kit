<?php

namespace Modules\Core\Tests\Unit\Services;

use Modules\Core\Services\FrontendService;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Mockery;
use Modules\Tenant\ValueObjects\DynamicTenantConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(FrontendService::class)]
#[Group('core-module')]
#[Group('core-services')]
class FrontendServiceTest extends TestCase
{
    private FrontendService $frontendService;

    private DynamicTenantConfig $mockConfig;

    private Redirector $mockRedirector;

    private ResponseFactory $mockResponseFactory;

    private string $baseUrl = 'https://quvel.127.0.0.1.nip.io';

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockConfig = $this->createTenantConfig();

        $this->mockRedirector      = Mockery::mock(Redirector::class);
        $this->mockResponseFactory = Mockery::mock(ResponseFactory::class);

        $mockRequest = Mockery::mock(Request::class);
        $mockRequest->shouldReceive('hasHeader')->with('X-Capacitor')->andReturn(false);

        $this->frontendService = (new FrontendService(
            redirector: $this->mockRedirector,
            responseFactory: $this->mockResponseFactory,
            request: $mockRequest,
        ))->setUrl($this->baseUrl);
    }

    /**
     * Test redirect to a frontend route for normal requests.
     */
    public function testRedirect(): void
    {
        $path        = '/dashboard';
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
    public function testRedirectWithQueryParameters(): void
    {
        $path        = '/profile';
        $params      = ['id' => 42, 'mode' => 'edit'];
        $expectedUrl = "$this->baseUrl$path?" . http_build_query($params);

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
    public function testRedirectWithCapacitorDeepScheme(): void
    {
        $path        = '/settings';
        $params      = ['setting' => 'dark'];
        $expectedUrl = "$this->baseUrl$path?" . http_build_query($params);

        $mockRequest = Mockery::mock(Request::class);
        $mockRequest->shouldReceive('hasHeader')->with('X-Capacitor')->andReturn(true);

        $config = new DynamicTenantConfig([
            ...$this->mockConfig->toArray(),
            'capacitor_scheme' => '_deep',
        ]);

        $frontendService = (new FrontendService(
            redirector: $this->mockRedirector,
            responseFactory: $this->mockResponseFactory,
            request: $mockRequest,
        ))->setUrl($this->baseUrl)
          ->setIsCapacitor(true)
          ->setCapacitorScheme('_deep');

        $this->mockRedirector
            ->shouldReceive('away')
            ->once()
            ->with($expectedUrl)
            ->andReturn(new RedirectResponse($expectedUrl));

        $response = $frontendService->redirect($path, $params);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals($expectedUrl, $response->getTargetUrl());
    }

    public function testRedirectWithCustomCapacitorScheme(): void
    {
        $path         = '/dashboard';
        $params       = ['user' => '123'];
        $customScheme = 'mycapacitor';
        $customUrl    = "$this->baseUrl$path?" . http_build_query($params);
        $expectedUrl  = preg_replace('/^https?/', $customScheme, $customUrl);

        $mockRequest = Mockery::mock(Request::class);
        $mockRequest->shouldReceive('hasHeader')->with('X-Capacitor')->andReturn(true);

        $frontendService = (new FrontendService(
            redirector: $this->mockRedirector,
            responseFactory: $this->mockResponseFactory,
            request: $mockRequest,
        ))->setUrl($this->baseUrl)
            ->setIsCapacitor(true)
            ->setCapacitorScheme($customScheme);

        $this->mockResponseFactory
            ->shouldReceive('view')
            ->once()
            ->with('redirect', Mockery::on(
                fn ($viewData) =>
                $viewData['message'] === null &&
                $viewData['schemeUrl'] === $expectedUrl
            ))
            ->andReturn(new RedirectResponse($expectedUrl));

        $response = $frontendService->redirect($path, $params);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals($expectedUrl, $response->getTargetUrl());
    }

    /**
     * Test generating a URL without redirecting.
     */
    public function testGetPageUrl(): void
    {
        $path        = '/settings';
        $params      = ['theme' => 'dark'];
        $expectedUrl = "$this->baseUrl$path?" . http_build_query($params);

        $url = $this->frontendService->getPageUrl($path, $params);

        $this->assertEquals($expectedUrl, $url);
    }

    public function testSetIsCapacitor(): void
    {
        $this->frontendService->setIsCapacitor(true);
        $reflectionValue = (new \ReflectionObject($this->frontendService))->getProperty('isCapacitor');
        $reflectionValue->setAccessible(true);
        $this->assertTrue($reflectionValue->getValue($this->frontendService));
    }
}
