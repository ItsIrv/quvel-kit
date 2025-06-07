<?php

namespace Modules\Core\Tests\Unit\Services\Security;

use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Modules\Core\Services\Security\GoogleRecaptchaVerifier;
use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\ValueObjects\DynamicTenantConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

#[CoversClass(GoogleRecaptchaVerifier::class)]
#[Group('core-module')]
#[Group('core-services')]
class GoogleRecaptchaVerifierTest extends TestCase
{
    private Request|MockObject $request;
    private HttpClient|MockObject $httpClient;
    private GoogleRecaptchaVerifier $verifier;
    private TenantContext|MockObject $mockTenantContext;

    protected function setUp(): void
    {
        parent::setUp();

        $this->request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['ip'])
            ->getMock();

        $this->httpClient = $this->getMockBuilder(HttpClient::class)
            ->disableOriginalConstructor()
            ->addMethods(['asForm'])
            ->getMock();

        // Mock tenant context
        $this->mockTenantContext = $this->createMock(TenantContext::class);

        $this->verifier = new GoogleRecaptchaVerifier(
            $this->request,
            $this->httpClient,
            $this->mockTenantContext,
        );
    }

    #[TestDox('returns false when no secret key is configured')]
    public function testReturnsFalseWhenNoSecretKeyIsConfigured(): void
    {
        // Mock getConfigValue to return empty
        $this->mockTenantContext->expects($this->once())
            ->method('getConfigValue')
            ->with('recaptcha_secret_key')
            ->willReturn(null);

        // Verify no HTTP request will be made
        $this->httpClient->expects($this->never())->method('asForm');

        $result = $this->verifier->verify('test-token');

        $this->assertFalse($result);
    }

    #[TestDox('verifies token successfully with Google API')]
    public function testVerifiesTokenSuccessfullyWithGoogleApi(): void
    {
        // Mock getConfigValue to return secret key
        $this->mockTenantContext->expects($this->once())
            ->method('getConfigValue')
            ->with('recaptcha_secret_key')
            ->willReturn('test-secret-key');

        $this->request->expects($this->once())
            ->method('ip')
            ->willReturn('192.168.1.1');

        $response = $this->createMock(Response::class);
        $response->expects($this->once())
            ->method('json')
            ->with('success')
            ->willReturn(true);

        $formRequest = $this->createMock(\Illuminate\Http\Client\PendingRequest::class);
        $formRequest->expects($this->once())
            ->method('post')
            ->with(
                'https://www.google.com/recaptcha/api/siteverify',
                [
                    'secret'   => 'test-secret-key',
                    'response' => 'test-token',
                    'remoteip' => '192.168.1.1',
                ],
            )
            ->willReturn($response);

        $this->httpClient->expects($this->once())
            ->method('asForm')
            ->willReturn($formRequest);

        $result = $this->verifier->verify('test-token');

        $this->assertTrue($result);
    }

    #[TestDox('uses provided IP address when given')]
    public function testUsesProvidedIpAddressWhenGiven(): void
    {
        // Mock getConfigValue to return secret key
        $this->mockTenantContext->expects($this->once())
            ->method('getConfigValue')
            ->with('recaptcha_secret_key')
            ->willReturn('test-secret-key');

        // Should not call request->ip() when IP is provided
        $this->request->expects($this->never())->method('ip');

        $response = $this->createMock(Response::class);
        $response->expects($this->once())
            ->method('json')
            ->with('success')
            ->willReturn(true);

        $formRequest = $this->createMock(\Illuminate\Http\Client\PendingRequest::class);
        $formRequest->expects($this->once())
            ->method('post')
            ->with(
                'https://www.google.com/recaptcha/api/siteverify',
                [
                    'secret'   => 'test-secret-key',
                    'response' => 'test-token',
                    'remoteip' => '10.0.0.1',
                ],
            )
            ->willReturn($response);

        $this->httpClient->expects($this->once())
            ->method('asForm')
            ->willReturn($formRequest);

        $result = $this->verifier->verify('test-token', '10.0.0.1');

        $this->assertTrue($result);
    }

    #[TestDox('returns correct verification result based on Google response')]
    #[DataProvider('googleResponseProvider')]
    public function testReturnsCorrectVerificationResultBasedOnGoogleResponse(
        $googleResponse,
        bool $expectedResult,
    ): void {
        // Mock getConfigValue to return secret key
        $this->mockTenantContext->expects($this->once())
            ->method('getConfigValue')
            ->with('recaptcha_secret_key')
            ->willReturn('test-secret-key');

        $this->request->expects($this->once())
            ->method('ip')
            ->willReturn('192.168.1.1');

        $response = $this->createMock(Response::class);
        $response->expects($this->once())
            ->method('json')
            ->with('success')
            ->willReturn($googleResponse);

        $formRequest = $this->createMock(\Illuminate\Http\Client\PendingRequest::class);
        $formRequest->expects($this->once())
            ->method('post')
            ->willReturn($response);

        $this->httpClient->expects($this->once())
            ->method('asForm')
            ->willReturn($formRequest);

        $result = $this->verifier->verify('test-token');

        $this->assertEquals($expectedResult, $result);
    }

    public static function googleResponseProvider(): array
    {
        return [
            'success response'     => [true, true],
            'failure response'     => [false, false],
            'null response'        => [null, false],
            'string true response' => ['true', false],
            'numeric 1 response'   => [1, false],
        ];
    }
}