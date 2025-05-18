<?php

namespace Modules\Core\Tests\Unit\Http\Middleware\Config;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Mockery;
use Mockery\MockInterface;
use Modules\Core\Http\Middleware\Config\CheckValue;
use Modules\Core\Services\FrontendService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

#[CoversClass(CheckValue::class)]
#[Group('core-module')]
#[Group('core-middleware')]
final class CheckValueTest extends TestCase
{
    /**
     * Request mock instance.
     */
    private Request|MockInterface $request;

    /**
     * FrontendService mock instance.
     */
    private FrontendService|MockInterface $frontendService;

    /**
     * Next closure.
     */
    private Closure $next;

    /**
     * Middleware instance.
     */
    private CheckValue $middleware;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->request         = Mockery::mock(Request::class);
        $this->frontendService = Mockery::mock(FrontendService::class);
        $this->next            = function ($request) {
            return response('next was called');
        };

        $this->middleware = new CheckValue();

        // Bind the frontend service to the container
        app()->instance(FrontendService::class, $this->frontendService);
    }

    #[TestDox('It should proceed when config value matches expected value')]
    public function testProceedsWhenConfigValueMatchesExpectedValue(): void
    {
        // Arrange
        $key      = 'app.debug';
        $expected = 'true';

        // Mock the config function
        config(['app.debug' => true]);

        // Act
        $response = $this->middleware->handle($this->request, $this->next, $key, $expected);

        // Assert
        $this->assertEquals('next was called', $response->getContent());
    }

    #[TestDox('It should return JSON response when request wants JSON and config value does not match')]
    public function testReturnsJsonResponseWhenRequestWantsJsonAndConfigValueDoesNotMatch(): void
    {
        // Arrange
        $key      = 'app.debug';
        $expected = 'true';

        // Mock the config function
        config(['app.debug' => false]);

        // Setup request to want JSON
        $this->request->shouldReceive('wantsJson')
            ->once()
            ->andReturn(true);

        // Act
        $response = $this->middleware->handle($this->request, $this->next, $key, $expected);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(403, $response->getStatusCode());
    }

    #[TestDox('It should return redirect response when request does not want JSON and config value does not match')]
    public function testReturnsRedirectResponseWhenRequestDoesNotWantJsonAndConfigValueDoesNotMatch(): void
    {
        // Arrange
        $key      = 'app.debug';
        $expected = 'true';

        // Mock the config function
        config(['app.debug' => false]);

        // Setup request to not want JSON
        $this->request->shouldReceive('wantsJson')
            ->once()
            ->andReturn(false);

        // Setup frontend service with exact message parameter
        $redirectResponse = Mockery::mock(RedirectResponse::class);
        $this->frontendService->shouldReceive('redirect')
            ->once()
            ->with('', ['message' => 'common::feature.status.info.notAvailable'])
            ->andReturn($redirectResponse);

        // Act
        $response = $this->middleware->handle($this->request, $this->next, $key, $expected);

        // Assert
        $this->assertSame($redirectResponse, $response);
    }

    #[TestDox('It should handle different expected value types correctly')]
    public function testHandlesDifferentExpectedValueTypesCorrectly(): void
    {
        // Test numeric value
        config(['app.value' => 42]);
        $response = $this->middleware->handle($this->request, $this->next, 'app.value', '42');
        $this->assertEquals('next was called', $response->getContent());

        // Test false value
        config(['app.value' => false]);
        $response = $this->middleware->handle($this->request, $this->next, 'app.value', 'false');
        $this->assertEquals('next was called', $response->getContent());

        // Test null value
        config(['app.value' => null]);
        $response = $this->middleware->handle($this->request, $this->next, 'app.value', 'null');
        $this->assertEquals('next was called', $response->getContent());

        // Test string value
        config(['app.value' => 'test']);
        $response = $this->middleware->handle($this->request, $this->next, 'app.value', 'test');
        $this->assertEquals('next was called', $response->getContent());
    }
}
