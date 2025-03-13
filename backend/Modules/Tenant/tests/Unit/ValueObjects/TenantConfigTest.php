<?php

namespace Modules\Tenant\Tests\Unit\ValueObjects;

use Modules\Tenant\Enums\TenantConfigVisibility;
use Modules\Tenant\ValueObjects\TenantConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(TenantConfig::class)]
#[Group('tenant-module')]
#[Group('tenant-valueobjects')]
class TenantConfigTest extends TestCase
{
    /**
     * Test creating a `TenantConfig` from an array.
     */
    public function testFromArrayCreatesInstanceCorrectly(): void
    {
        $data = [
            'api_url'           => 'https://api.example.com',
            'app_url'           => 'https://example.com',
            'app_name'          => 'Example Tenant',
            'app_env'           => 'production',
            'internal_api_url'  => 'http://internal.example.com',
            'debug'             => true,
            'mail_from_name'    => 'Example Support',
            'mail_from_address' => 'support@example.com',
            '__visibility'      => [
                'app_name'         => TenantConfigVisibility::PUBLIC ->value,
                'api_url'          => TenantConfigVisibility::PUBLIC ->value,
                'internal_api_url' => TenantConfigVisibility::PROTECTED ->value,
            ],
        ];

        $config = TenantConfig::fromArray($data);

        $this->assertEquals('https://api.example.com', $config->apiUrl);
        $this->assertEquals('https://example.com', $config->appUrl);
        $this->assertEquals('Example Tenant', $config->appName);
        $this->assertEquals('production', $config->appEnv);
        $this->assertEquals('http://internal.example.com', $config->internalApiUrl);
        $this->assertTrue($config->debug);
        $this->assertEquals('Example Support', $config->mailFromName);
        $this->assertEquals('support@example.com', $config->mailFromAddress);

        // Visibility check
        $this->assertEquals([
            'app_name'         => TenantConfigVisibility::PUBLIC ,
            'api_url'          => TenantConfigVisibility::PUBLIC ,
            'internal_api_url' => TenantConfigVisibility::PROTECTED ,
        ], $config->visibility);
    }

    /**
     * Test `fromArray` handles missing values and uses defaults.
     */
    public function testFromArrayHandlesMissingValues(): void
    {
        $config = TenantConfig::fromArray([]);

        $this->assertEquals('', $config->apiUrl);
        $this->assertEquals('', $config->appUrl);
        $this->assertEquals('', $config->appName);
        $this->assertEquals('', $config->appEnv);
        $this->assertNull($config->internalApiUrl);
        $this->assertFalse($config->debug);
        $this->assertEquals('', $config->mailFromName);
        $this->assertEquals('', $config->mailFromAddress);
        $this->assertEquals([], $config->visibility);
    }

    /**
     * Test `toArray` correctly converts object back to array.
     */
    public function testToArrayReturnsCorrectFormat(): void
    {
        $config = new TenantConfig(
            apiUrl: 'https://api.example.com',
            appUrl: 'https://example.com',
            appName: 'Example Tenant',
            appEnv: 'production',
            internalApiUrl: 'http://internal.example.com',
            debug: true,
            mailFromName: 'Example Support',
            mailFromAddress: 'support@example.com',
            visibility: [
                'app_name'         => TenantConfigVisibility::PUBLIC ,
                'api_url'          => TenantConfigVisibility::PUBLIC ,
                'internal_api_url' => TenantConfigVisibility::PROTECTED ,
            ],
        );

        $result = $config->toArray();

        $expected = [
            'api_url'           => 'https://api.example.com',
            'app_url'           => 'https://example.com',
            'app_name'          => 'Example Tenant',
            'internal_api_url'  => 'http://internal.example.com',
            'app_env'           => 'production',
            'debug'             => true,
            'mail_from_name'    => 'Example Support',
            'mail_from_address' => 'support@example.com',
            '__visibility'      => [
                'app_name'         => TenantConfigVisibility::PUBLIC ->value,
                'api_url'          => TenantConfigVisibility::PUBLIC ->value,
                'internal_api_url' => TenantConfigVisibility::PROTECTED ->value,
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test `fromArray` properly handles invalid visibility values.
     */
    public function testFromArrayHandlesInvalidVisibilityValues(): void
    {
        $data = [
            '__visibility' => [
                'app_name' => 'invalid_value', // Should default to PRIVATE
                'api_url'  => TenantConfigVisibility::PUBLIC ->value,
            ],
        ];

        $config = TenantConfig::fromArray($data);

        $this->assertEquals([
            'app_name' => TenantConfigVisibility::PRIVATE ,
            'api_url'  => TenantConfigVisibility::PUBLIC ,
        ], $config->visibility);
    }
}
