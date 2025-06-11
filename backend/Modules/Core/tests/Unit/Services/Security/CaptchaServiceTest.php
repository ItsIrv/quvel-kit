<?php

namespace Modules\Core\Tests\Unit\Services\Security;

use Modules\Core\Contracts\Security\CaptchaVerifierInterface;
use Modules\Core\Services\Security\CaptchaService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @testdox CaptchaService
 */
#[CoversClass(CaptchaService::class)]
#[Group('core-module')]
#[Group('core-services')]
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

    #[TestDox('verifies token successfully')]
    public function testVerifiesTokenSuccessfully(): void
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

    #[TestDox('verifies token with null IP')]
    public function testVerifiesTokenWithNullIp(): void
    {
        $token = 'valid-token';

        $this->verifier->expects($this->once())
            ->method('verify')
            ->with($token, null)
            ->willReturn(true);

        $result = $this->service->verify($token);

        $this->assertTrue($result);
    }

    #[TestDox('returns false when verification fails')]
    public function testReturnsFalseWhenVerificationFails(): void
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

    #[TestDox('delegates to verifier implementation')]
    public function testDelegatesToVerifierImplementation(): void
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

    #[TestDox('works with different verifier implementations')]
    public function testWorksWithDifferentVerifierImplementations(): void
    {
        // Create a custom verifier implementation
        $customVerifier = new class () implements CaptchaVerifierInterface {
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
