<?php

namespace Modules\Core\Tests\Unit\Http\Middleware\Security;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Mockery;
use Mockery\MockInterface;
use Modules\Core\Http\Middleware\Security\VerifyCaptcha;
use Modules\Core\Services\Security\CaptchaService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

#[CoversClass(VerifyCaptcha::class)]
#[Group('core-module')]
#[Group('core-middleware')]
final class VerifyCaptchaTest extends TestCase
{
    /**
     * Request mock instance.
     */
    private Request|MockInterface $request;

    /**
     * CaptchaService mock instance.
     */
    private CaptchaService|MockInterface $captchaService;

    /**
     * Next closure.
     */
    private Closure $next;

    /**
     * Middleware instance.
     */
    private VerifyCaptcha $middleware;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->request = Mockery::mock(Request::class);
        $this->captchaService = Mockery::mock(CaptchaService::class);
        $this->next = function ($request) {
            return response('next was called');
        };

        $this->middleware = new VerifyCaptcha($this->captchaService);
    }

    #[TestDox('It should proceed when captcha token is valid')]
    public function testProceedsWhenCaptchaTokenIsValid(): void
    {
        // Arrange
        $token = 'valid-captcha-token';
        $ipAddress = '192.168.1.1';

        $this->request->shouldReceive('input')
            ->once()
            ->with('captcha_token')
            ->andReturn($token);

        $this->request->shouldReceive('ip')
            ->once()
            ->andReturn($ipAddress);

        $this->captchaService->shouldReceive('verify')
            ->once()
            ->with($token, $ipAddress)
            ->andReturn(true);

        // Act
        $response = $this->middleware->handle($this->request, $this->next);

        // Assert
        $this->assertEquals('next was called', $response->getContent());
    }

    #[TestDox('It should return error response when captcha token is missing')]
    public function testReturnsErrorResponseWhenCaptchaTokenIsMissing(): void
    {
        // Arrange
        $this->request->shouldReceive('input')
            ->once()
            ->with('captcha_token')
            ->andReturn(null);

        // Act
        $response = $this->middleware->handle($this->request, $this->next);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals(['message' => 'Captcha verification failed.'], $response->getData(true));
    }

    #[TestDox('It should return error response when captcha token is invalid')]
    public function testReturnsErrorResponseWhenCaptchaTokenIsInvalid(): void
    {
        // Arrange
        $token = 'invalid-captcha-token';
        $ipAddress = '192.168.1.1';

        $this->request->shouldReceive('input')
            ->once()
            ->with('captcha_token')
            ->andReturn($token);

        $this->request->shouldReceive('ip')
            ->once()
            ->andReturn($ipAddress);

        $this->captchaService->shouldReceive('verify')
            ->once()
            ->with($token, $ipAddress)
            ->andReturn(false);

        // Act
        $response = $this->middleware->handle($this->request, $this->next);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals(['message' => 'Captcha verification failed.'], $response->getData(true));
    }
}
