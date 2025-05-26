<?php

namespace Modules\Core\Tests\Unit\Services\Security;

use Modules\Core\Contracts\Security\CaptchaVerifierInterface;
use Modules\Core\Services\Security\CaptchaService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

#[CoversClass(CaptchaService::class)]
class CaptchaServiceTest extends TestCase
{
    private CaptchaVerifierInterface&MockObject $verifier;
    private CaptchaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->verifier = $this->createMock(CaptchaVerifierInterface::class);
        $this->service = new CaptchaService($this->verifier);
    }

    #[Test]
    public function it_verifies_token_successfully(): void
    {
        $token = 'valid-token';
        $ip = '192.168.1.1';
        
        $this->verifier->expects($this->once())
            ->method('verify')
            ->with($token, $ip)
            ->willReturn(true);
            
        $result = $this->service->verify($token, $ip);
        
        $this->assertTrue($result);
    }

    #[Test]
    public function it_verifies_token_with_null_ip(): void
    {
        $token = 'valid-token';
        
        $this->verifier->expects($this->once())
            ->method('verify')
            ->with($token, null)
            ->willReturn(true);
            
        $result = $this->service->verify($token);
        
        $this->assertTrue($result);
    }

    #[Test]
    public function it_returns_false_when_verification_fails(): void
    {
        $token = 'invalid-token';
        $ip = '192.168.1.1';
        
        $this->verifier->expects($this->once())
            ->method('verify')
            ->with($token, $ip)
            ->willReturn(false);
            
        $result = $this->service->verify($token, $ip);
        
        $this->assertFalse($result);
    }

    #[Test]
    public function it_delegates_to_verifier_implementation(): void
    {
        $token = 'test-token';
        $ip = '10.0.0.1';
        
        // Test that it properly delegates to the verifier
        $this->verifier->expects($this->once())
            ->method('verify')
            ->with(
                $this->identicalTo($token),
                $this->identicalTo($ip)
            )
            ->willReturn(true);
            
        $this->service->verify($token, $ip);
    }

    #[Test]
    public function it_works_with_different_verifier_implementations(): void
    {
        // Create a custom verifier implementation
        $customVerifier = new class implements CaptchaVerifierInterface {
            public function verify(string $token, ?string $ip = null): bool
            {
                return $token === 'special-token';
            }
        };
        
        $service = new CaptchaService($customVerifier);
        
        $this->assertTrue($service->verify('special-token'));
        $this->assertFalse($service->verify('regular-token'));
    }
}