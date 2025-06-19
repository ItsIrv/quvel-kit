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

    /**
     * Test that get() handles '0' string as empty value.
     */
    public function testGetReturnsNullWhenValueIsZeroString(): void
    {
        $cast = new DynamicTenantConfigCast();
        $model = new Tenant();

        $result = $cast->get($model, 'config', '0', []);
        $this->assertNull($result);
    }

    /**
     * Test that get() handles legacy format with __visibility key.
     */
    public function testGetHandlesLegacyVisibilityFormat(): void
    {
        $cast = new DynamicTenantConfigCast();
        $model = new Tenant();

        $legacyData = [
            'app_name' => 'Legacy App',
            'app_url' => 'https://legacy.example.com',
            'secret_key' => 'super-secret',
            '__visibility' => [
                'app_name' => 'public',
                'app_url' => 'protected',
                'secret_key' => 'private',
            ],
        ];

        $jsonValue = json_encode($legacyData, JSON_THROW_ON_ERROR);
        $result = $cast->get($model, 'config', $jsonValue, []);

        $this->assertInstanceOf(DynamicTenantConfig::class, $result);
        $this->assertEquals('Legacy App', $result->get('app_name'));
        $this->assertEquals('https://legacy.example.com', $result->get('app_url'));
        $this->assertEquals('super-secret', $result->get('secret_key'));

        // Verify visibility was correctly parsed
        $this->assertEquals(TenantConfigVisibility::PUBLIC, $result->getVisibility('app_name'));
        $this->assertEquals(TenantConfigVisibility::PROTECTED, $result->getVisibility('app_url'));
        $this->assertEquals(TenantConfigVisibility::PRIVATE, $result->getVisibility('secret_key'));
    }

    /**
     * Test that get() handles legacy format with invalid visibility values.
     */
    public function testGetHandlesLegacyFormatWithInvalidVisibility(): void
    {
        $cast = new DynamicTenantConfigCast();
        $model = new Tenant();

        $legacyData = [
            'app_name' => 'Test App',
            '__visibility' => [
                'app_name' => 'invalid_visibility_level',
            ],
        ];

        $jsonValue = json_encode($legacyData, JSON_THROW_ON_ERROR);
        $result = $cast->get($model, 'config', $jsonValue, []);

        $this->assertInstanceOf(DynamicTenantConfig::class, $result);
        $this->assertEquals('Test App', $result->get('app_name'));

        // Invalid visibility should default to PRIVATE
        $this->assertEquals(TenantConfigVisibility::PRIVATE, $result->getVisibility('app_name'));
    }

    /**
     * Test that get() handles legacy format with non-string visibility values.
     */
    public function testGetHandlesLegacyFormatWithNonStringVisibility(): void
    {
        $cast = new DynamicTenantConfigCast();
        $model = new Tenant();

        $legacyData = [
            'app_name' => 'Test App',
            '__visibility' => [
                'app_name' => TenantConfigVisibility::PUBLIC, // Enum instance instead of string
            ],
        ];

        $jsonValue = json_encode($legacyData, JSON_THROW_ON_ERROR);
        $result = $cast->get($model, 'config', $jsonValue, []);

        $this->assertInstanceOf(DynamicTenantConfig::class, $result);
        $this->assertEquals('Test App', $result->get('app_name'));
        $this->assertEquals(TenantConfigVisibility::PUBLIC, $result->getVisibility('app_name'));
    }

    /**
     * Test that get() handles direct config array format.
     */
    public function testGetHandlesDirectConfigArrayFormat(): void
    {
        $cast = new DynamicTenantConfigCast();
        $model = new Tenant();

        $directConfig = [
            'app_name' => 'Direct Config App',
            'app_url' => 'https://direct.example.com',
            'debug' => false,
        ];

        $jsonValue = json_encode($directConfig, JSON_THROW_ON_ERROR);
        $result = $cast->get($model, 'config', $jsonValue, []);

        $this->assertInstanceOf(DynamicTenantConfig::class, $result);
        $this->assertEquals('Direct Config App', $result->get('app_name'));
        $this->assertEquals('https://direct.example.com', $result->get('app_url'));
        $this->assertFalse($result->get('debug'));

        // Should have no visibility settings (defaults to PRIVATE)
        $this->assertEquals(TenantConfigVisibility::PRIVATE, $result->getVisibility('app_name'));
    }

    /**
     * Test that get() handles new format with config and visibility keys.
     */
    public function testGetHandlesNewFormatWithConfigAndVisibilityKeys(): void
    {
        $cast = new DynamicTenantConfigCast();
        $model = new Tenant();

        $newFormatData = [
            'config' => [
                'app_name' => 'New Format App',
                'app_url' => 'https://new.example.com',
            ],
            'visibility' => [
                'app_name' => TenantConfigVisibility::PUBLIC->value,
                'app_url' => TenantConfigVisibility::PROTECTED->value,
            ],
        ];

        $jsonValue = json_encode($newFormatData, JSON_THROW_ON_ERROR);
        $result = $cast->get($model, 'config', $jsonValue, []);

        $this->assertInstanceOf(DynamicTenantConfig::class, $result);
        $this->assertEquals('New Format App', $result->get('app_name'));
        $this->assertEquals('https://new.example.com', $result->get('app_url'));
        $this->assertEquals(TenantConfigVisibility::PUBLIC, $result->getVisibility('app_name'));
        $this->assertEquals(TenantConfigVisibility::PROTECTED, $result->getVisibility('app_url'));
    }

    /**
     * Test that get() handles malformed JSON gracefully.
     */
    public function testGetThrowsExceptionForMalformedJson(): void
    {
        $cast = new DynamicTenantConfigCast();
        $model = new Tenant();

        $this->expectException(JsonException::class);
        $cast->get($model, 'config', '{invalid json}', []);
    }

    /**
     * Test that get() handles empty JSON object.
     */
    public function testGetHandlesEmptyJsonObject(): void
    {
        $cast = new DynamicTenantConfigCast();
        $model = new Tenant();

        $result = $cast->get($model, 'config', '{}', []);

        $this->assertInstanceOf(DynamicTenantConfig::class, $result);
        $this->assertNull($result->get('nonexistent_key'));
    }

    /**
     * Test that set() handles non-DynamicTenantConfig values (arrays).
     */
    public function testSetHandlesNonDynamicTenantConfigValues(): void
    {
        $cast = new DynamicTenantConfigCast();
        $model = new Tenant();

        $arrayValue = [
            'config' => ['app_name' => 'Array App'],
            'visibility' => ['app_name' => 'public'],
        ];

        $result = $cast->set($model, 'config', $arrayValue, []);

        $this->assertIsString($result);
        $decodedResult = json_decode($result, true, JSON_THROW_ON_ERROR);
        $this->assertEquals('Array App', $decodedResult['config']['app_name']);
        $this->assertEquals('public', $decodedResult['visibility']['app_name']);
    }

    /**
     * Test that set() handles string values.
     */
    public function testSetHandlesStringValues(): void
    {
        $cast = new DynamicTenantConfigCast();
        $model = new Tenant();

        $stringValue = '{"app_name": "String App"}';
        $result = $cast->set($model, 'config', $stringValue, []);

        $this->assertIsString($result);
        // String values get JSON encoded
        $this->assertEquals('"' . addslashes($stringValue) . '"', $result);
    }

    /**
     * Test that set() handles scalar values.
     */
    public function testSetHandlesScalarValues(): void
    {
        $cast = new DynamicTenantConfigCast();
        $model = new Tenant();

        $result = $cast->set($model, 'config', 'simple string', []);
        $this->assertEquals('"simple string"', $result);

        $result = $cast->set($model, 'config', 42, []);
        $this->assertEquals('42', $result);

        $result = $cast->set($model, 'config', true, []);
        $this->assertEquals('true', $result);
    }

    /**
     * Test comprehensive integration scenario.
     */
    public function testComprehensiveIntegrationScenario(): void
    {
        $cast = new DynamicTenantConfigCast();
        $model = new Tenant();

        // Create a complex config object
        $originalConfig = new DynamicTenantConfig([
            'app_name' => 'Integration Test App',
            'app_url' => 'https://integration.example.com',
            'database_url' => 'mysql://user:pass@localhost/db',
            'cache_driver' => 'redis',
            'debug' => false,
        ], [
            'app_name' => TenantConfigVisibility::PUBLIC,
            'app_url' => TenantConfigVisibility::PUBLIC,
            'database_url' => TenantConfigVisibility::PRIVATE,
            'cache_driver' => TenantConfigVisibility::PROTECTED,
            'debug' => TenantConfigVisibility::PROTECTED,
        ]);

        // Convert to JSON via set()
        $jsonString = $cast->set($model, 'config', $originalConfig, []);
        $this->assertIsString($jsonString);

        // Convert back to object via get()
        $restoredConfig = $cast->get($model, 'config', $jsonString, []);
        $this->assertInstanceOf(DynamicTenantConfig::class, $restoredConfig);

        // Verify all values are preserved
        $this->assertEquals($originalConfig->get('app_name'), $restoredConfig->get('app_name'));
        $this->assertEquals($originalConfig->get('app_url'), $restoredConfig->get('app_url'));
        $this->assertEquals($originalConfig->get('database_url'), $restoredConfig->get('database_url'));
        $this->assertEquals($originalConfig->get('cache_driver'), $restoredConfig->get('cache_driver'));
        $this->assertEquals($originalConfig->get('debug'), $restoredConfig->get('debug'));

        // Verify all visibility settings are preserved
        $this->assertEquals($originalConfig->getVisibility('app_name'), $restoredConfig->getVisibility('app_name'));
        $this->assertEquals($originalConfig->getVisibility('app_url'), $restoredConfig->getVisibility('app_url'));
        $this->assertEquals($originalConfig->getVisibility('database_url'), $restoredConfig->getVisibility('database_url'));
        $this->assertEquals($originalConfig->getVisibility('cache_driver'), $restoredConfig->getVisibility('cache_driver'));
        $this->assertEquals($originalConfig->getVisibility('debug'), $restoredConfig->getVisibility('debug'));
    }
}
