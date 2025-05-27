<?php

namespace Modules\Core\Tests\Unit\Services\Security;

use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Http\Request;
use Modules\Core\Contracts\Security\CaptchaVerifierInterface;
use Modules\Core\Services\Security\GoogleRecaptchaVerifier;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @testdox GoogleRecaptchaVerifier
 */
#[CoversClass(GoogleRecaptchaVerifier::class)]
#[Group('core-module')]
#[Group('core-services')]
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

    #[TestDox('implements CaptchaVerifierInterface')]
    public function testImplementsCaptchaVerifierInterface(): void
    {
        $this->assertInstanceOf(CaptchaVerifierInterface::class, $this->verifier);
    }

    #[TestDox('has correct constructor dependencies')]
    public function testHasCorrectConstructorDependencies(): void
    {
        // Test that the verifier can be instantiated with required dependencies
        $verifier = new GoogleRecaptchaVerifier($this->request, $this->httpClient);
        $this->assertInstanceOf(GoogleRecaptchaVerifier::class, $verifier);
    }

    #[TestDox('has verify method with correct signature')]
    public function testHasVerifyMethodWithCorrectSignature(): void
    {
        // Test that the verify method exists and has the correct signature
        $this->assertTrue(method_exists($this->verifier, 'verify'));
        
        $reflection = new \ReflectionMethod($this->verifier, 'verify');
        $parameters = $reflection->getParameters();
        
        $this->assertCount(2, $parameters);
        $this->assertEquals('token', $parameters[0]->getName());
        $this->assertEquals('ip', $parameters[1]->getName());
        $this->assertTrue($parameters[1]->allowsNull());
    }
}