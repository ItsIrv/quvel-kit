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
     * Provides a minimal valid config array.
     */
    private function getMinimalValidConfig(): array
    {
        return [
            'app_name'          => 'TenantX',
            'app_env'           => 'testing',
            'app_key'           => 'base64:testkey==',
            'app_debug'         => false,
            'app_timezone'      => 'UTC',
            'app_url'           => 'http://localhost',
            'frontend_url'      => 'http://frontend.local',
            'mail_host'         => 'smtp.mailtrap.io',
            'mail_from_address' => 'noreply@tenantx.test',
            'mail_from_name'    => 'TenantX Support',
        ];
    }

    public function testFromArrayCreatesInstanceCorrectly(): void
    {
        $data = array_merge($this->getMinimalValidConfig(), [
            'internal_api_url' => 'http://internal.local',
            'app_debug'        => true,
            '__visibility'     => [
                'app_name'         => TenantConfigVisibility::PUBLIC ->value,
                'internal_api_url' => TenantConfigVisibility::PROTECTED ->value,
            ],
        ]);

        $config = TenantConfig::fromArray($data);

        $this->assertEquals('TenantX', $config->appName);
        $this->assertEquals('testing', $config->appEnv);
        $this->assertEquals('http://localhost', $config->appUrl);
        $this->assertEquals('http://frontend.local', $config->frontendUrl);
        $this->assertEquals('http://internal.local', $config->internalApiUrl);
        $this->assertTrue($config->appDebug);
        $this->assertEquals('TenantX Support', $config->mailFromName);
        $this->assertEquals('noreply@tenantx.test', $config->mailFromAddress);

        $this->assertEquals([
            'app_name'         => TenantConfigVisibility::PUBLIC ,
            'internal_api_url' => TenantConfigVisibility::PROTECTED ,
        ], $config->visibility);
    }

    public function testFromArrayHandlesMissingOptionalValues(): void
    {
        $config = TenantConfig::fromArray($this->getMinimalValidConfig());

        $this->assertEquals('TenantX', $config->appName);
        $this->assertEquals('testing', $config->appEnv);
        $this->assertEquals('http://localhost', $config->appUrl);
        $this->assertEquals('TenantX Support', $config->mailFromName);
        $this->assertEquals('noreply@tenantx.test', $config->mailFromAddress);

        $this->assertNull($config->internalApiUrl);
        $this->assertFalse($config->appDebug);
        $this->assertEquals([], $config->visibility);
    }

    public function testToArrayOutputsCorrectData(): void
    {
        $config = TenantConfig::fromArray(array_merge($this->getMinimalValidConfig(), [
            'app_key'          => 'base64:testkey==',
            'app_debug'        => true,
            'internal_api_url' => 'http://internal.local',
            'capacitor_scheme' => 'app',
            '__visibility'     => [
                'app_name' => TenantConfigVisibility::PUBLIC ->value,
            ],
        ]));

        $array = $config->toArray();

        $this->assertEquals('TenantX', $array['app_name']);
        $this->assertEquals('base64:testkey==', $array['app_key']);
        $this->assertEquals(true, $array['app_debug']);
        $this->assertEquals('http://internal.local', $array['internal_api_url']);
        $this->assertEquals('app', $array['capacitor_scheme']);

        $this->assertEquals([
            'app_name' => TenantConfigVisibility::PUBLIC ->value,
        ], $array['__visibility']);
    }

    public function testFromArrayHandlesInvalidVisibilityValues(): void
    {
        $config = TenantConfig::fromArray(array_merge($this->getMinimalValidConfig(), [
            '__visibility' => [
                'app_name' => 'not_a_valid_value',
                'app_url'  => TenantConfigVisibility::PROTECTED ->value,
            ],
        ]));

        $this->assertEquals([
            'app_name' => TenantConfigVisibility::PRIVATE ,
            'app_url'  => TenantConfigVisibility::PROTECTED ,
        ], $config->visibility);
    }
}
