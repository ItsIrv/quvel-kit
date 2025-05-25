<?php

namespace Modules\Auth\Tests\Unit\Services;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Mockery;
use Modules\Auth\Services\HmacService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(HmacService::class)]
#[Group('auth-module')]
#[Group('auth-services')]
class HmacServiceTest extends TestCase
{
    private HmacService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the configuration repository
        $config = Mockery::mock(ConfigRepository::class);

        // Initialize the service with the mocked config
        $config->shouldReceive('get')
            ->with('auth.socialite.hmac_secret')
            ->andReturn('test_secret_key');

        $this->service = new HmacService($config);
    }

    public function testSignGeneratesValidHmac(): void
    {
        $value = 'test_value';

        // Generate HMAC
        $hmac = $this->service->sign($value);

        // Manually calculate expected HMAC using the same secret
        $expectedHmac = hash_hmac('sha256', $value, 'test_secret_key');

        // Assert
        $this->assertEquals($expectedHmac, $hmac);
    }

    public function testVerifyReturnsTrueForValidHmac(): void
    {
        $value = 'test_value';

        // Generate a valid HMAC
        $hmac = hash_hmac('sha256', $value, 'test_secret_key');

        // Verify the HMAC
        $isValid = $this->service->verify($value, $hmac);

        // Assert
        $this->assertTrue($isValid);
    }

    public function testVerifyReturnsFalseForInvalidHmac(): void
    {
        $value       = 'test_value';
        $invalidHmac = 'invalid_hmac';

        // Verify the invalid HMAC
        $isValid = $this->service->verify($value, $invalidHmac);

        // Assert
        $this->assertFalse($isValid);
    }

    public function testSignWithHmacReturnsFormattedValue(): void
    {
        $value = 'test_value';

        // Generate the result
        $result = $this->service->signWithHmac($value);

        // Manually calculate expected value
        $expectedHmac   = hash_hmac('sha256', $value, 'test_secret_key');
        $expectedResult = "$value.$expectedHmac";

        // Assert
        $this->assertEquals($expectedResult, $result);
    }

    public function testExtractAndVerifyReturnsOriginalValueWhenValid(): void
    {
        $value = 'test_value';

        // Generate a signed value
        $hmac        = hash_hmac('sha256', $value, 'test_secret_key');
        $signedValue = "$value.$hmac";

        // Extract and verify
        $originalValue = $this->service->extractAndVerify($signedValue);

        // Assert
        $this->assertEquals($value, $originalValue);
    }

    public function testExtractAndVerifyReturnsNullWhenHmacIsInvalid(): void
    {
        $signedValue = 'test_value.invalid_hmac';

        // Extract and verify
        $result = $this->service->extractAndVerify($signedValue);

        // Assert
        $this->assertNull($result);
    }

    public function testExtractAndVerifyReturnsNullForMalformedString(): void
    {
        $signedValue = 'invalid_format_string';

        // Extract and verify
        $result = $this->service->extractAndVerify($signedValue);

        // Assert
        $this->assertNull($result);
    }
}
