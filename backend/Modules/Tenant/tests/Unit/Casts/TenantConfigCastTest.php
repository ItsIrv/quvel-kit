<?php

namespace Modules\Tenant\Tests\Unit\Casts;

use Modules\Tenant\Casts\TenantConfigCast;
use Modules\Tenant\Enums\TenantConfigVisibility;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\ValueObjects\TenantConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(TenantConfigCast::class)]
#[Group('tenant-module')]
#[Group('tenant-casts')]
class TenantConfigCastTest extends TestCase
{
    /**
     * Test that TenantConfigCast returns null when value is empty.
     */
    public function test_get_returns_null_when_value_is_empty(): void
    {
        $cast = new TenantConfigCast;
        $model = new Tenant;

        $result = $cast->get($model, 'config', null, []);
        $this->assertNull($result);

        $result = $cast->get($model, 'config', '', []);
        $this->assertNull($result);
    }

    /**
     * Test that TenantConfigCast correctly casts JSON string to TenantConfig object.
     */
    public function test_get_casts_json_to_tenant_config(): void
    {
        $cast = new TenantConfigCast;
        $model = new Tenant;
        $configData = [
            'api_url' => 'https://api.example.com',
            'app_url' => 'https://app.example.com',
            'app_name' => 'Example App',
            'app_env' => 'production',
            'internal_api_url' => 'https://internal-api.example.com',
            'debug' => true,
            'mail_from_name' => 'Example',
            'mail_from_address' => 'no-reply@example.com',
            '__visibility' => [
                'api_url' => TenantConfigVisibility::PUBLIC->value,
                'app_name' => TenantConfigVisibility::PUBLIC->value,
            ],
        ];

        $jsonValue = json_encode($configData);
        $result = $cast->get($model, 'config', $jsonValue, []);

        $this->assertInstanceOf(TenantConfig::class, $result);
        $this->assertEquals('https://api.example.com', $result->apiUrl);
        $this->assertEquals('https://app.example.com', $result->appUrl);
        $this->assertEquals('Example App', $result->appName);
        $this->assertEquals('production', $result->appEnv);
        $this->assertEquals('https://internal-api.example.com', $result->internalApiUrl);
        $this->assertTrue($result->debug);
        $this->assertEquals('Example', $result->mailFromName);
        $this->assertEquals('no-reply@example.com', $result->mailFromAddress);
        $this->assertCount(2, $result->visibility);
        $this->assertEquals(
            TenantConfigVisibility::PUBLIC,
            $result->visibility['api_url'],
        );
        $this->assertEquals(
            TenantConfigVisibility::PUBLIC,
            $result->visibility['app_name'],
        );
    }

    /**
     * Test that TenantConfigCast returns null when set value is null.
     */
    public function test_set_returns_null_when_value_is_null(): void
    {
        $cast = new TenantConfigCast;
        $model = new Tenant;

        $result = $cast->set($model, 'config', null, []);
        $this->assertNull($result);
    }

    /**
     * Test that TenantConfigCast correctly casts TenantConfig object to JSON string.
     */
    public function test_set_casts_tenant_config_to_json(): void
    {
        $cast = new TenantConfigCast;
        $model = new Tenant;

        $config = new TenantConfig(
            apiUrl: 'https://api.example.com',
            appUrl: 'https://app.example.com',
            appName: 'Example App',
            appEnv: 'production',
            internalApiUrl: 'https://internal-api.example.com',
            debug: true,
            mailFromName: 'Example',
            mailFromAddress: 'no-reply@example.com',
            visibility: [
                'api_url' => TenantConfigVisibility::PUBLIC,
                'app_name' => TenantConfigVisibility::PUBLIC,
            ],
        );

        $result = $cast->set($model, 'config', $config, []);

        $this->assertIsString($result);

        $decodedResult = json_decode($result, true);
        $this->assertEquals('https://api.example.com', $decodedResult['api_url']);
        $this->assertEquals('https://app.example.com', $decodedResult['app_url']);
        $this->assertEquals('Example App', $decodedResult['app_name']);
        $this->assertEquals('production', $decodedResult['app_env']);
        $this->assertEquals('https://internal-api.example.com', $decodedResult['internal_api_url']);
        $this->assertTrue($decodedResult['debug']);
        $this->assertEquals('Example', $decodedResult['mail_from_name']);
        $this->assertEquals('no-reply@example.com', $decodedResult['mail_from_address']);
        $this->assertCount(2, $decodedResult['__visibility']);
        $this->assertEquals(TenantConfigVisibility::PUBLIC->value, $decodedResult['__visibility']['api_url']);
        $this->assertEquals(TenantConfigVisibility::PUBLIC->value, $decodedResult['__visibility']['app_name']);
    }

    /**
     * Test that TenantConfigCast correctly casts array to JSON string.
     */
    public function test_set_casts_array_to_json(): void
    {
        $cast = new TenantConfigCast;
        $model = new Tenant;

        $configArray = [
            'api_url' => 'https://api.example.com',
            'app_url' => 'https://app.example.com',
            'app_name' => 'Example App',
            'app_env' => 'production',
            'internal_api_url' => 'https://internal-api.example.com',
            'debug' => true,
            'mail_from_name' => 'Example',
            'mail_from_address' => 'no-reply@example.com',
            '__visibility' => [
                'api_url' => TenantConfigVisibility::PUBLIC->value,
                'app_name' => TenantConfigVisibility::PUBLIC->value,
            ],
        ];

        $result = $cast->set($model, 'config', $configArray, []);

        $this->assertIsString($result);

        $decodedResult = json_decode($result, true);
        $this->assertEquals('https://api.example.com', $decodedResult['api_url']);
        $this->assertEquals('https://app.example.com', $decodedResult['app_url']);
        $this->assertEquals('Example App', $decodedResult['app_name']);
        $this->assertEquals('production', $decodedResult['app_env']);
        $this->assertEquals('https://internal-api.example.com', $decodedResult['internal_api_url']);
        $this->assertTrue($decodedResult['debug']);
        $this->assertEquals('Example', $decodedResult['mail_from_name']);
        $this->assertEquals('no-reply@example.com', $decodedResult['mail_from_address']);
        $this->assertCount(2, $decodedResult['__visibility']);
    }
}
