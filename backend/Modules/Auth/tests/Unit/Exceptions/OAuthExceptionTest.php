<?php

namespace Modules\Auth\Tests\Unit\Exceptions;

use Modules\Core\Services\FrontendService;
use Illuminate\Http\RedirectResponse;
use Mockery;
use Modules\Auth\Enums\OAuthStatusEnum;
use Modules\Auth\Exceptions\OAuthException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(OAuthException::class)]
#[Group('auth-module')]
#[Group('auth-exceptions')]
class OAuthExceptionTest extends TestCase
{
    public function testExceptionMessageAndRender(): void
    {
        // Arrange
        $status    = OAuthStatusEnum::INVALID_NONCE;
        $exception = new OAuthException($status);

        // We mock FrontendService and bind it to the container, so that "app(FrontendService::class)"
        // will return our mock.
        $mockFrontendService = Mockery::mock(FrontendService::class);

        // Suppose redirectPage returns a RedirectResponse
        $redirectResponse = new RedirectResponse('/redirect?message=' . $status->value);

        // Expect the call "redirect('', ['message' => 'auth::status.errors.invalidNonce'])"
        $mockFrontendService
            ->shouldReceive('redirect')
            ->once()
            ->with('', ['message' => $status->value])
            ->andReturn($redirectResponse);

        // Bind the mock to the container
        $this->app->instance(FrontendService::class, $mockFrontendService);

        // Act
        $response = $exception->render();

        // Assert
        $this->assertEquals($status->value, $exception->getMessage());
        $this->assertSame($redirectResponse, $response);
        $this->assertStringContainsString($status->value, $response->getTargetUrl());
    }

    public function testExceptionWithPrevious(): void
    {
        // If you want to test the 'previous' logic
        $previous = new \Exception('Some underlying error');
        $status   = OAuthStatusEnum::INVALID_PROVIDER;

        $exception = new OAuthException($status, $previous);

        $this->assertSame($previous, $exception->getPrevious());
        $this->assertEquals($status->value, $exception->getMessage());
    }
}
