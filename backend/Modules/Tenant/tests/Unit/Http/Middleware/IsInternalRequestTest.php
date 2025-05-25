<?php

namespace Modules\Tenant\Tests\Unit\Http\Middleware;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Mockery;
use Mockery\MockInterface;
use Modules\Tenant\Http\Middleware\IsInternalRequest;
use Modules\Tenant\Services\RequestPrivacy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

#[CoversClass(IsInternalRequest::class)]
#[Group('tenant-module')]
#[Group('tenant-middleware')]
final class IsInternalRequestTest extends TestCase
{
    /**
     * @var RequestPrivacy|MockInterface
     */
    private RequestPrivacy $requestPrivacyService;

    /**
     * @var IsInternalRequest
     */
    private IsInternalRequest $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requestPrivacyService = Mockery::mock(RequestPrivacy::class);
        $this->middleware            = new IsInternalRequest(
            $this->requestPrivacyService,
            $this->app,
        );
    }

    #[TestDox('It should allow the request to proceed when it is an internal request')]
    public function testAllowsRequestWhenInternal(): void
    {
        // Arrange
        $request          = Mockery::mock(Request::class);
        $expectedResponse = 'response';

        $this->requestPrivacyService->shouldReceive('isInternalRequest')
            ->once()
            ->andReturn(true);

        $next = function ($passedRequest) use ($request, $expectedResponse) {
            $this->assertSame($request, $passedRequest);
            return $expectedResponse;
        };

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertSame($expectedResponse, $response);
    }

    #[TestDox('It should abort with 401 when the request is not internal')]
    public function testAbortsWhenNotInternal(): void
    {
        // Arrange
        $request = Mockery::mock(Request::class);
        $next    = function () {
            return 'This should not be called';
        };

        $this->requestPrivacyService->shouldReceive('isInternalRequest')
            ->once()
            ->andReturn(false);

        // Assert
        $this->expectException(HttpResponseException::class);

        // Act
        $this->middleware->handle($request, $next);
    }
}
