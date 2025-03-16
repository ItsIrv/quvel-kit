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
            ->with('auth.oauth.hmac_secret')
            ->andReturn('test_secret_key');

        $this->service = new HmacService($config);
    }

    public function test_sign_generates_valid_hmac(): void
    {
        $value = 'test_value';

        // Generate HMAC
        $hmac = $this->service->sign($value);

        // Manually calculate expected HMAC using the same secret
        $expectedHmac = hash_hmac('sha256', $value, 'test_secret_key');

        // Assert
        $this->assertEquals($expectedHmac, $hmac);
    }

    public function test_verify_returns_true_for_valid_hmac(): void
    {
        $value = 'test_value';

        // Generate a valid HMAC
        $hmac = hash_hmac('sha256', $value, 'test_secret_key');

        // Verify the HMAC
        $isValid = $this->service->verify($value, $hmac);

        // Assert
        $this->assertTrue($isValid);
    }

    public function test_verify_returns_false_for_invalid_hmac(): void
    {
        $value = 'test_value';
        $invalidHmac = 'invalid_hmac';

        // Verify the invalid HMAC
        $isValid = $this->service->verify($value, $invalidHmac);

        // Assert
        $this->assertFalse($isValid);
    }

    public function test_sign_with_hmac_returns_formatted_value(): void
    {
        $value = 'test_value';

        // Generate the result
        $result = $this->service->signWithHmac($value);

        // Manually calculate expected value
        $expectedHmac = hash_hmac('sha256', $value, 'test_secret_key');
        $expectedResult = "$value.$expectedHmac";

        // Assert
        $this->assertEquals($expectedResult, $result);
    }

    public function test_extract_and_verify_returns_original_value_when_valid(): void
    {
        $value = 'test_value';

        // Generate a signed value
        $hmac = hash_hmac('sha256', $value, 'test_secret_key');
        $signedValue = "$value.$hmac";

        // Extract and verify
        $originalValue = $this->service->extractAndVerify($signedValue);

        // Assert
        $this->assertEquals($value, $originalValue);
    }

    public function test_extract_and_verify_returns_null_when_hmac_is_invalid(): void
    {
        $signedValue = 'test_value.invalid_hmac';

        // Extract and verify
        $result = $this->service->extractAndVerify($signedValue);

        // Assert
        $this->assertNull($result);
    }

    public function test_extract_and_verify_returns_null_for_malformed_string(): void
    {
        $signedValue = 'invalid_format_string';

        // Extract and verify
        $result = $this->service->extractAndVerify($signedValue);

        // Assert
        $this->assertNull($result);
    }
}
