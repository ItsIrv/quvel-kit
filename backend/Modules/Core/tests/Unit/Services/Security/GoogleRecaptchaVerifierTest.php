<?php

namespace Modules\Core\Tests\Unit\Services\Security;

use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Modules\Core\Services\Security\GoogleRecaptchaVerifier;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

#[CoversClass(GoogleRecaptchaVerifier::class)]
#[Group('core-module')]
#[Group('core-services')]
class GoogleRecaptchaVerifierTest extends TestCase
{
    private GoogleRecaptchaVerifier $verifier;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Use the real HTTP client from the container
        $this->verifier = new GoogleRecaptchaVerifier(
            app(Request::class),
            app(HttpClient::class)
        );
    }

    #[TestDox('returns false when no secret key is configured')]
    public function testReturnsFalseWhenNoSecretKeyIsConfigured(): void
    {
        // Set config to null
        config(['recaptcha_secret_key' => null]);

        // Fake HTTP requests to ensure no requests are made
        Http::fake();

        $result = $this->verifier->verify('test-token');

        $this->assertFalse($result);
        
        // Verify no HTTP requests were made
        Http::assertNothingSent();
    }

    #[TestDox('verifies token successfully with Google API')]
    public function testVerifiesTokenSuccessfullyWithGoogleApi(): void
    {
        // Set config value
        config(['recaptcha_secret_key' => 'test-secret-key']);


        // Fake HTTP response
        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true], 200)
        ]);

        $result = $this->verifier->verify('test-token');

        $this->assertTrue($result);
        
        // Verify the correct request was made
        Http::assertSent(function ($request) {
            return $request->url() === 'https://www.google.com/recaptcha/api/siteverify' &&
                   $request->data()['secret'] === 'test-secret-key' &&
                   $request->data()['response'] === 'test-token' &&
                   isset($request->data()['remoteip']); // Just verify IP is present
        });
    }

    #[TestDox('uses provided IP address when given')]
    public function testUsesProvidedIpAddressWhenGiven(): void
    {
        // Set config value
        config(['recaptcha_secret_key' => 'test-secret-key']);

        // IP is provided directly, so request->ip() won't be called

        // Fake HTTP response
        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true], 200)
        ]);

        $result = $this->verifier->verify('test-token', '10.0.0.1');

        $this->assertTrue($result);
        
        // Verify the correct request was made with provided IP
        Http::assertSent(function ($request) {
            return $request->url() === 'https://www.google.com/recaptcha/api/siteverify' &&
                   $request->data()['secret'] === 'test-secret-key' &&
                   $request->data()['response'] === 'test-token' &&
                   $request->data()['remoteip'] === '10.0.0.1';
        });
    }

    #[TestDox('returns correct verification result based on Google response')]
    #[DataProvider('googleResponseProvider')]
    public function testReturnsCorrectVerificationResultBasedOnGoogleResponse(
        $googleResponse,
        bool $expectedResult,
    ): void {
        // Set config value
        config(['recaptcha_secret_key' => 'test-secret-key']);


        // Fake HTTP response with different success values
        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response(['success' => $googleResponse], 200)
        ]);

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