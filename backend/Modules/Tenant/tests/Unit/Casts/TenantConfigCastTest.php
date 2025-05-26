<?php

namespace Modules\Tenant\Tests\Unit\Casts;

use JsonException;
use Modules\Tenant\Casts\DynamicTenantConfigCast;
use Modules\Tenant\Enums\TenantConfigVisibility;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\ValueObjects\DynamicTenantConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(DynamicTenantConfigCast::class)]
#[Group('tenant-module')]
#[Group('tenant-casts')]
class TenantConfigCastTest extends TestCase
{
    /**
     * Test that DynamicTenantConfigCast returns null when value is empty.
     *
     * @throws JsonException
     */
    public function testGetReturnsNullWhenValueIsEmpty(): void
    {
        $cast  = new DynamicTenantConfigCast();
        $model = new Tenant();

        $result = $cast->get($model, 'config', null, []);
        $this->assertNull($result);

        $result = $cast->get($model, 'config', '', []);
        $this->assertNull($result);
    }

    /**
     * Test that DynamicTenantConfigCast correctly casts JSON string to DynamicTenantConfig object.
     *
     * @throws JsonException
     */
    public function testGetCastsJsonToTenantConfig(): void
    {
        $cast       = new DynamicTenantConfigCast();
        $model      = new Tenant();
        $configData = [
            'app_url'           => 'https://api.example.com',
            'frontend_url'      => 'https://app.example.com',
            'app_name'          => 'Example App',
            'app_env'           => 'testing',
            'internal_api_url'  => 'https://internal-api.example.com',
            'app_debug'         => true,
            'mail_from_name'    => 'Example',
            'mail_from_address' => 'no-reply@example.com',
            '__visibility'      => [
                'app_url'  => TenantConfigVisibility::PUBLIC ->value,
                'app_name' => TenantConfigVisibility::PUBLIC ->value,
            ],
            'capacitor_scheme'  => null,
        ];

        $jsonValue = json_encode($configData, JSON_THROW_ON_ERROR);
        $result    = $cast->get($model, 'config', $jsonValue, []);

        $this->assertInstanceOf(DynamicTenantConfig::class, $result);
        $this->assertEquals('https://api.example.com', $result->get('app_url'));
        $this->assertEquals('https://app.example.com', $result->get('frontend_url'));
        $this->assertEquals('Example App', $result->get('app_name'));
        $this->assertEquals('testing', $result->get('app_env'));
        $this->assertEquals('https://internal-api.example.com', $result->get('internal_api_url'));
        $this->assertTrue($result->get('app_debug'));
        $this->assertEquals('Example', $result->get('mail_from_name'));
        $this->assertEquals('no-reply@example.com', $result->get('mail_from_address'));
        
        // Check visibility
        $this->assertEquals(
            TenantConfigVisibility::PUBLIC,
            $result->getVisibility('app_url'),
        );
        $this->assertEquals(
            TenantConfigVisibility::PUBLIC,
            $result->getVisibility('app_name'),
        );
        $this->assertNull($result->get('capacitor_scheme'));
    }

    /**
     * Test that DynamicTenantConfigCast returns null when set value is null.
     *
     * @throws JsonException
     */
    public function testSetReturnsNullWhenValueIsNull(): void
    {
        $cast  = new DynamicTenantConfigCast();
        $model = new Tenant();

        $result = $cast->set($model, 'config', null, []);
        $this->assertNull($result);
    }

    /**
     * Test that DynamicTenantConfigCast correctly casts DynamicTenantConfig object to JSON string.
     *
     * @throws JsonException
     */
    public function testSetCastsTenantConfigToJson(): void
    {
        $cast  = new DynamicTenantConfigCast();
        $model = new Tenant();

        $config = new DynamicTenantConfig([
            'app_url' => 'https://api.example.com',
            'frontend_url' => 'https://app.example.com',
            'app_name' => 'Example App',
            'app_env' => 'testing',
            'internal_api_url' => 'https://internal-api.example.com',
            'app_debug' => true,
            'mail_from_name' => 'Example',
            'mail_from_address' => 'no-reply@example.com',
        ], [
            'app_url' => TenantConfigVisibility::PUBLIC,
            'app_name' => TenantConfigVisibility::PUBLIC,
        ]);

        $result = $cast->set($model, 'config', $config, []);

        $this->assertIsString($result);

        $decodedResult = json_decode($result, true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('https://api.example.com', $decodedResult['config']['app_url']);
        $this->assertEquals('https://app.example.com', $decodedResult['config']['frontend_url']);
        $this->assertEquals('Example App', $decodedResult['config']['app_name']);
        $this->assertEquals('testing', $decodedResult['config']['app_env']);
        $this->assertEquals('https://internal-api.example.com', $decodedResult['config']['internal_api_url']);
        $this->assertTrue($decodedResult['config']['app_debug']);
        $this->assertEquals('Example', $decodedResult['config']['mail_from_name']);
        $this->assertEquals('no-reply@example.com', $decodedResult['config']['mail_from_address']);
        $this->assertCount(2, $decodedResult['visibility']);
        $this->assertEquals(TenantConfigVisibility::PUBLIC ->value, $decodedResult['visibility']['app_url']);
        $this->assertEquals(TenantConfigVisibility::PUBLIC ->value, $decodedResult['visibility']['app_name']);
    }

    /**
     * Test that DynamicTenantConfigCast correctly casts array to JSON string.
     *
     * @throws JsonException
     */
    public function testSetCastsArrayToJson(): void
    {
        $cast  = new DynamicTenantConfigCast();
        $model = new Tenant();

        $configArray = [
            'config' => [
                'frontend_url'      => 'https://app.example.com',
                'app_url'           => 'https://api.example.com',
                'app_name'          => 'Example App',
                'app_env'           => 'testing',
                'internal_api_url'  => 'https://internal-api.example.com',
                'app_debug'         => true,
                'mail_from_name'    => 'Example',
                'mail_from_address' => 'no-reply@example.com',
            ],
            'visibility' => [
                'app_url'  => TenantConfigVisibility::PUBLIC ->value,
                'app_name' => TenantConfigVisibility::PUBLIC ->value,
            ],
        ];

        $result = $cast->set($model, 'config', $configArray, []);

        $this->assertIsString($result);

        $decodedResult = json_decode($result, true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('https://api.example.com', $decodedResult['config']['app_url']);
        $this->assertEquals('https://app.example.com', $decodedResult['config']['frontend_url']);
        $this->assertEquals('Example App', $decodedResult['config']['app_name']);
        $this->assertEquals('testing', $decodedResult['config']['app_env']);
        $this->assertEquals('https://internal-api.example.com', $decodedResult['config']['internal_api_url']);
        $this->assertTrue($decodedResult['config']['app_debug']);
        $this->assertEquals('Example', $decodedResult['config']['mail_from_name']);
        $this->assertEquals('no-reply@example.com', $decodedResult['config']['mail_from_address']);
        $this->assertCount(2, $decodedResult['visibility']);
    }
}
