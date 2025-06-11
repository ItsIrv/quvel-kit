<?php

namespace Modules\Core\Tests\Unit\Http\Actions\Debug;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Actions\Debug\ShowProxyInfoAction;
use Modules\Core\Services\FrontendService;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

class ShowProxyInfoActionTest extends TestCase
{
    private ShowProxyInfoAction $action;
    private Config $config;
    private FrontendService $frontendService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = $this->createMock(Config::class);
        $this->frontendService = $this->createMock(FrontendService::class);

        $this->action = new ShowProxyInfoAction(
            $this->frontendService,
            $this->config
        );
    }

    #[TestDox('returns 404 when debug is disabled')]
    public function testReturns404WhenDebugIsDisabled(): void
    {
        $this->config->expects($this->once())
            ->method('get')
            ->with('app.debug')
            ->willReturn(false);

        $request = Request::create('/debug/proxy', 'GET');

        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);

        $this->action->__invoke($request);
    }

    #[TestDox('returns proxy information when debug is enabled')]
    public function testReturnsProxyInformationWhenDebugIsEnabled(): void
    {
        // Mock config calls
        $this->config->expects($this->atLeast(6))
            ->method('get')
            ->willReturnCallback(function ($key, $default = null) {
                return match ($key) {
                    'app.debug' => true,
                    'app.env' => 'testing',
                    'trustedproxy.proxies' => '127.0.0.1',
                    'trustedproxy.headers' => 'FORWARDED',
                    'octane.server' => 'swoole',
                    'app.url' => 'https://example.com',
                    default => $default,
                };
            });

        $this->frontendService->expects($this->once())
            ->method('getUrl')
            ->willReturn('https://frontend.example.com');

        $request = Request::create('https://example.com/debug/proxy?test=1', 'GET');

        $response = $this->action->__invoke($request);

        $this->assertInstanceOf(JsonResponse::class, $response);

        $data = $response->getData(true);

        // Verify response structure
        $this->assertArrayHasKey('environment', $data);
        $this->assertArrayHasKey('request_info', $data);
        $this->assertArrayHasKey('proxy_detection', $data);
        $this->assertArrayHasKey('forwarded_headers', $data);
        $this->assertArrayHasKey('server_variables', $data);
        $this->assertArrayHasKey('url_generation', $data);
        $this->assertArrayHasKey('all_headers', $data);
        $this->assertArrayHasKey('timestamp', $data);
        $this->assertArrayHasKey('frontend_url', $data);

        // Verify environment data
        $this->assertTrue($data['environment']['APP_DEBUG']);
        $this->assertEquals('testing', $data['environment']['APP_ENV']);
        $this->assertEquals('127.0.0.1', $data['environment']['TRUST_PROXIES']);

        // Verify request info
        $this->assertEquals('GET', $data['request_info']['method']);
        $this->assertEquals('debug/proxy', $data['request_info']['path']);

        // Verify frontend URL
        $this->assertEquals('https://frontend.example.com', $data['frontend_url']);
    }

    #[TestDox('handles missing environment variables gracefully')]
    public function testHandlesMissingEnvironmentVariablesGracefully(): void
    {
        $this->config->expects($this->atLeast(6))
            ->method('get')
            ->willReturnCallback(function ($key, $default = null) {
                return match ($key) {
                    'app.debug' => true,
                    'app.env' => null,
                    'trustedproxy.proxies' => null,
                    'trustedproxy.headers' => null,
                    'octane.server' => null,
                    'app.url' => null,
                    default => $default,
                };
            });

        $this->frontendService->expects($this->once())->method('getUrl')->willReturn('http://localhost:3000');

        $request = Request::create('/debug/proxy', 'GET');

        $response = $this->action->__invoke($request);

        $this->assertInstanceOf(JsonResponse::class, $response);

        $data = $response->getData(true);

        // Should handle null values gracefully
        $this->assertNull($data['environment']['APP_ENV']);
        $this->assertNull($data['environment']['TRUST_PROXIES']);
        $this->assertNull($data['environment']['TRUSTED_PROXY_IPS']);
        $this->assertNull($data['environment']['OCTANE_SERVER']);
        $this->assertNull($data['url_generation']['app_url']);
    }
}
