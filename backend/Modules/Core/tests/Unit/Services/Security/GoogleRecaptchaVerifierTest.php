<?php

namespace Modules\Core\Tests\Unit\Services\Security;

use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Modules\Core\Contracts\Security\CaptchaVerifierInterface;
use Modules\Core\Services\Security\GoogleRecaptchaVerifier;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\ValueObjects\DynamicTenantConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

#[CoversClass(GoogleRecaptchaVerifier::class)]
class GoogleRecaptchaVerifierTest extends TestCase
{
    private Request&MockObject $request;
    private HttpClient&MockObject $httpClient;
    private GoogleRecaptchaVerifier $verifier;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->request = $this->createMock(Request::class);
        $this->httpClient = $this->createMock(HttpClient::class);
        $this->verifier = new GoogleRecaptchaVerifier($this->request, $this->httpClient);
    }

    #[Test]
    public function it_implements_captcha_verifier_interface(): void
    {
        $this->assertInstanceOf(CaptchaVerifierInterface::class, $this->verifier);
    }

    #[Test]
    public function it_verifies_valid_token_successfully(): void
    {
        // Set up tenant config
        $this->setupTenantConfig('test-secret-key');
        
        $token = 'valid-token';
        $ip = '192.168.1.1';
        
        $pendingRequest = $this->createMock(PendingRequest::class);
        $response = $this->createMock(Response::class);
        
        $this->httpClient->expects($this->once())
            ->method('asForm')
            ->willReturn($pendingRequest);
            
        $pendingRequest->expects($this->once())
            ->method('post')
            ->with(
                'https://www.google.com/recaptcha/api/siteverify',
                [
                    'secret' => 'test-secret-key',
                    'response' => $token,
                    'remoteip' => $ip,
                ]
            )
            ->willReturn($response);
            
        $response->expects($this->once())
            ->method('json')
            ->with('success')
            ->willReturn(true);
            
        $result = $this->verifier->verify($token, $ip);
        
        $this->assertTrue($result);
    }

    #[Test]
    public function it_uses_request_ip_when_ip_not_provided(): void
    {
        // Set up tenant config
        $this->setupTenantConfig('test-secret-key');
        
        $token = 'valid-token';
        $requestIp = '10.0.0.1';
        
        $this->request->expects($this->once())
            ->method('ip')
            ->willReturn($requestIp);
        
        $pendingRequest = $this->createMock(PendingRequest::class);
        $response = $this->createMock(Response::class);
        
        $this->httpClient->expects($this->once())
            ->method('asForm')
            ->willReturn($pendingRequest);
            
        $pendingRequest->expects($this->once())
            ->method('post')
            ->with(
                'https://www.google.com/recaptcha/api/siteverify',
                [
                    'secret' => 'test-secret-key',
                    'response' => $token,
                    'remoteip' => $requestIp,
                ]
            )
            ->willReturn($response);
            
        $response->expects($this->once())
            ->method('json')
            ->with('success')
            ->willReturn(true);
            
        $result = $this->verifier->verify($token);
        
        $this->assertTrue($result);
    }

    #[Test]
    public function it_returns_false_when_recaptcha_validation_fails(): void
    {
        // Set up tenant config
        $this->setupTenantConfig('test-secret-key');
        
        $token = 'invalid-token';
        
        $pendingRequest = $this->createMock(PendingRequest::class);
        $response = $this->createMock(Response::class);
        
        $this->httpClient->expects($this->once())
            ->method('asForm')
            ->willReturn($pendingRequest);
            
        $pendingRequest->expects($this->once())
            ->method('post')
            ->willReturn($response);
            
        $response->expects($this->once())
            ->method('json')
            ->with('success')
            ->willReturn(false);
            
        $result = $this->verifier->verify($token);
        
        $this->assertFalse($result);
    }

    #[Test]
    public function it_returns_false_when_no_secret_key_configured(): void
    {
        // Set up tenant config with no secret key
        $this->setupTenantConfig('');
        
        $token = 'any-token';
        
        // Should not make any HTTP requests
        $this->httpClient->expects($this->never())
            ->method('asForm');
            
        $result = $this->verifier->verify($token);
        
        $this->assertFalse($result);
    }

    #[Test]
    public function it_returns_false_when_secret_key_is_null(): void
    {
        // Set up tenant config with null secret key
        $this->setupTenantConfig(null);
        
        $token = 'any-token';
        
        // Should not make any HTTP requests
        $this->httpClient->expects($this->never())
            ->method('asForm');
            
        $result = $this->verifier->verify($token);
        
        $this->assertFalse($result);
    }

    #[Test]
    public function it_handles_non_boolean_success_response(): void
    {
        // Set up tenant config
        $this->setupTenantConfig('test-secret-key');
        
        $token = 'token';
        
        $pendingRequest = $this->createMock(PendingRequest::class);
        $response = $this->createMock(Response::class);
        
        $this->httpClient->expects($this->once())
            ->method('asForm')
            ->willReturn($pendingRequest);
            
        $pendingRequest->expects($this->once())
            ->method('post')
            ->willReturn($response);
            
        // Return string instead of boolean
        $response->expects($this->once())
            ->method('json')
            ->with('success')
            ->willReturn('true');
            
        $result = $this->verifier->verify($token);
        
        // Should be false because it's strictly checking for boolean true
        $this->assertFalse($result);
    }

    #[Test]
    public function it_handles_null_success_response(): void
    {
        // Set up tenant config
        $this->setupTenantConfig('test-secret-key');
        
        $token = 'token';
        
        $pendingRequest = $this->createMock(PendingRequest::class);
        $response = $this->createMock(Response::class);
        
        $this->httpClient->expects($this->once())
            ->method('asForm')
            ->willReturn($pendingRequest);
            
        $pendingRequest->expects($this->once())
            ->method('post')
            ->willReturn($response);
            
        // Return null (e.g., missing success field)
        $response->expects($this->once())
            ->method('json')
            ->with('success')
            ->willReturn(null);
            
        $result = $this->verifier->verify($token);
        
        $this->assertFalse($result);
    }

    /**
     * Helper method to set up tenant config for tests
     */
    private function setupTenantConfig(?string $secretKey): void
    {
        // Create a tenant with config
        $tenant = new Tenant();
        $tenant->id = 'test-tenant';
        
        $config = new DynamicTenantConfig();
        if ($secretKey !== null) {
            $config->set('recaptcha_secret_key', $secretKey);
        }
        
        // Set up the tenant context
        app()->bind('tenant', fn() => $tenant);
        
        // Mock the getTenantConfig helper to return our test value
        if (!function_exists('getTenantConfig')) {
            function getTenantConfig($key) {
                $tenant = app('tenant');
                if ($tenant && method_exists($tenant, 'getEffectiveConfig')) {
                    $config = $tenant->getEffectiveConfig();
                    return $config?->get($key);
                }
                return null;
            }
        }
        
        // Override the tenant's getEffectiveConfig method
        $tenant->setRelation('config', $config);
    }
}