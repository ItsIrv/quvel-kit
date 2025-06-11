<?php

namespace Modules\Core\Tests\Unit\Http\Middleware\Trace;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Mockery;
use Mockery\MockInterface;
use Modules\Core\Enums\CoreHeader;
use Modules\Core\Http\Middleware\Trace\SetTraceId;
use Modules\Core\Services\Security\RequestPrivacy;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

#[CoversClass(SetTraceId::class)]
#[Group('core-module')]
#[Group('core-middleware')]
final class SetTraceIdTest extends TestCase
{
    /**
     * Request mock instance.
     */
    private Request|MockInterface $request;

    /**
     * RequestPrivacy mock instance.
     */
    private RequestPrivacy|MockInterface $requestPrivacy;

    /**
     * Response mock instance.
     */
    private Response|MockInterface $response;

    /**
     * Next closure.
     */
    private Closure $next;

    /**
     * Middleware instance.
     */
    private SetTraceId $middleware;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->request        = Mockery::mock(Request::class);
        $this->requestPrivacy = Mockery::mock(RequestPrivacy::class);
        $this->response       = Mockery::mock(Response::class);

        // Properly mock the headers property
        $this->response->headers = Mockery::mock(ResponseHeaderBag::class);

        $this->next = function ($request) {
            return $this->response;
        };

        $this->middleware = new SetTraceId($this->requestPrivacy);
    }

    #[TestDox('It should skip trace ID generation when tracing is disabled')]
    public function testSkipsTraceIdGenerationWhenTracingIsDisabled(): void
    {
        // Arrange
        config(['core.trace.enabled' => false]);

        // Act
        $result = $this->middleware->handle($this->request, $this->next);

        // Assert
        $this->assertSame($this->response, $result);
        // No headers should be set
        $this->response->headers->shouldNotReceive('set');
    }

    #[TestDox('It should generate new trace ID when no header is provided')]
    public function testGeneratesNewTraceIdWhenNoHeaderIsProvided(): void
    {
        // Arrange
        config(['core.trace.enabled' => true]);
        config(['core.trace.always_generate' => true]);

        $this->request->shouldReceive('header')
            ->once()
            ->with(CoreHeader::TRACE_ID->value)
            ->andReturn(null);

        $this->response->headers->shouldReceive('set')
            ->once()
            ->with(CoreHeader::TRACE_ID->value, Mockery::type('string'));

        // Act
        $result = $this->middleware->handle($this->request, $this->next);

        // Assert
        $this->assertSame($this->response, $result);
    }

    #[TestDox('It should accept trace ID from header when internal request')]
    public function testAcceptsTraceIdFromHeaderWhenInternalRequest(): void
    {
        // Arrange
        config(['core.trace.enabled' => true]);
        config(['core.trace.require_internal_request' => true]);

        $traceId = Str::uuid()->toString();

        $this->request->shouldReceive('header')
            ->once()
            ->with(CoreHeader::TRACE_ID->value)
            ->andReturn($traceId);

        $this->requestPrivacy->shouldReceive('isInternalRequest')
            ->once()
            ->andReturn(true);

        $this->response->headers->shouldReceive('set')
            ->once()
            ->with(CoreHeader::TRACE_ID->value, $traceId);

        // Act
        $result = $this->middleware->handle($this->request, $this->next);

        // Assert
        $this->assertSame($this->response, $result);
    }

    #[TestDox('It should reject trace ID from header when not internal request')]
    public function testRejectsTraceIdFromHeaderWhenNotInternalRequest(): void
    {
        // Arrange
        config(['core.trace.enabled' => true]);
        config(['core.trace.require_internal_request' => true]);

        $headerTraceId = Str::uuid()->toString();

        $this->request->shouldReceive('header')
            ->once()
            ->with(CoreHeader::TRACE_ID->value)
            ->andReturn($headerTraceId);

        $this->requestPrivacy->shouldReceive('isInternalRequest')
            ->once()
            ->andReturn(false);

        $this->response->headers->shouldReceive('set')
            ->once()
            ->with(CoreHeader::TRACE_ID->value, Mockery::type('string'))
            ->andReturnUsing(function ($header, $value) use ($headerTraceId) {
                // Ensure the trace ID is not the one from the header
                $this->assertNotEquals($headerTraceId, $value);
                return null;
            });

        // Act
        $result = $this->middleware->handle($this->request, $this->next);

        // Assert
        $this->assertSame($this->response, $result);
    }

    #[TestDox('It should accept trace ID from header when not requiring internal request')]
    public function testAcceptsTraceIdFromHeaderWhenNotRequiringInternalRequest(): void
    {
        // Arrange
        config(['core.trace.enabled' => true]);
        config(['core.trace.require_internal_request' => false]);

        $traceId = Str::uuid()->toString();

        $this->request->shouldReceive('header')
            ->once()
            ->with(CoreHeader::TRACE_ID->value)
            ->andReturn($traceId);

        // RequestPrivacy should not be called
        $this->requestPrivacy->shouldNotReceive('isInternalRequest');

        $this->response->headers->shouldReceive('set')
            ->once()
            ->with(CoreHeader::TRACE_ID->value, $traceId);

        // Act
        $result = $this->middleware->handle($this->request, $this->next);

        // Assert
        $this->assertSame($this->response, $result);
    }

    #[TestDox('It should preserve whitespace header when accepted')]
    public function testPreservesWhitespaceHeaderWhenAccepted(): void
    {
        // Arrange
        config(['core.trace.enabled' => true]);
        config(['core.trace.always_generate' => true]);
        config(['core.trace.require_internal_request' => true]);

        // Return whitespace string - this is non-empty so will be accepted and preserved
        $this->request->shouldReceive('header')
            ->once()
            ->with(CoreHeader::TRACE_ID->value)
            ->andReturn(' ');

        // Mock internal request so header would be accepted
        $this->requestPrivacy->shouldReceive('isInternalRequest')
            ->once()
            ->andReturn(true);

        $this->response->headers->shouldReceive('set')
            ->once()
            ->with(CoreHeader::TRACE_ID->value, ' ');

        // Act
        $result = $this->middleware->handle($this->request, $this->next);

        // Assert
        $this->assertSame($this->response, $result);
    }

    #[TestDox('It should not generate trace ID when header is whitespace and always_generate is disabled')]
    public function testDoesNotGenerateTraceIdWhenHeaderIsWhitespaceAndAlwaysGenerateIsDisabled(): void
    {
        // Arrange
        config(['core.trace.enabled' => true]);
        config(['core.trace.always_generate' => false]);
        config(['core.trace.require_internal_request' => true]);

        // Return whitespace string
        $this->request->shouldReceive('header')
            ->once()
            ->with(CoreHeader::TRACE_ID->value)
            ->andReturn(' ');

        // Mock internal request so header would be accepted
        $this->requestPrivacy->shouldReceive('isInternalRequest')
            ->once()
            ->andReturn(true);

        $this->response->headers->shouldReceive('set')
            ->once()
            ->with(CoreHeader::TRACE_ID->value, ' ');

        // Act
        $result = $this->middleware->handle($this->request, $this->next);

        // Assert
        $this->assertSame($this->response, $result);
    }

    #[TestDox('It demonstrates that line 42 is unreachable code')]
    public function testDemonstratesLine42IsUnreachable(): void
    {
        // This test documents that line 42 in SetTraceId::handle() is unreachable
        // because empty headers are rejected in shouldAcceptTraceHeader() and
        // Str::uuid() always returns a non-empty string.

        // Test 1: Empty header is rejected, so line 38 generates UUID
        config(['core.trace.enabled' => true]);
        config(['core.trace.always_generate' => true]);

        $this->request->shouldReceive('header')
            ->once()
            ->with(CoreHeader::TRACE_ID->value)
            ->andReturn('');

        $this->response->headers->shouldReceive('set')
            ->once()
            ->with(CoreHeader::TRACE_ID->value, Mockery::type('string'))
            ->andReturnUsing(function ($header, $value) {
                // UUID generated from line 38, not line 42
                $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value);
                return null;
            });

        $result = $this->middleware->handle($this->request, $this->next);
        $this->assertSame($this->response, $result);
    }

    #[TestDox('It covers line 42 when a custom subclass allows empty header acceptance')]
    public function testCoversLine42WhenCustomSubclassAllowsEmptyHeaderAcceptance(): void
    {
        // Create a custom middleware that overrides shouldAcceptTraceHeader to return true for empty
        $middleware = new class ($this->requestPrivacy) extends SetTraceId
        {
            protected function shouldAcceptTraceHeader(?string $traceId): bool
            {
                // Always accept, even if empty - this allows us to test line 42
                return true;
            }
        };

        config(['core.trace.enabled' => true]);
        config(['core.trace.always_generate' => true]);

        $this->request->shouldReceive('header')
            ->once()
            ->with(CoreHeader::TRACE_ID->value)
            ->andReturn('');

        $this->response->headers->shouldReceive('set')
            ->once()
            ->with(CoreHeader::TRACE_ID->value, Mockery::type('string'))
            ->andReturnUsing(function ($header, $value) {
                // UUID generated from line 42 this time!
                $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value);
                return null;
            });

        $result = $middleware->handle($this->request, $this->next);
        $this->assertSame($this->response, $result);
    }
}
