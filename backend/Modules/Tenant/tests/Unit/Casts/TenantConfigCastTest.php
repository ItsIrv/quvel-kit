<?php

namespace Modules\Tenant\Tests\Unit\Casts;

use JsonException;
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
     *
     * @throws JsonException
     */
    public function testGetReturnsNullWhenValueIsEmpty(): void
    {
        $cast  = new TenantConfigCast();
        $model = new Tenant();

        $result = $cast->get($model, 'config', null, []);
        $this->assertNull($result);

        $result = $cast->get($model, 'config', '', []);
        $this->assertNull($result);
    }

    /**
     * Test that TenantConfigCast correctly casts JSON string to TenantConfig object.
     *
     * @throws JsonException
     */
    public function testGetCastsJsonToTenantConfig(): void
    {
        $cast       = new TenantConfigCast();
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

        $this->assertInstanceOf(TenantConfig::class, $result);
        $this->assertEquals('https://api.example.com', $result->appUrl);
        $this->assertEquals('https://app.example.com', $result->frontendUrl);
        $this->assertEquals('Example App', $result->appName);
        $this->assertEquals('testing', $result->appEnv);
        $this->assertEquals('https://internal-api.example.com', $result->internalApiUrl);
        $this->assertTrue($result->appDebug);
        $this->assertEquals('Example', $result->mailFromName);
        $this->assertEquals('no-reply@example.com', $result->mailFromAddress);
        $this->assertCount(2, $result->visibility);
        $this->assertEquals(
            TenantConfigVisibility::PUBLIC ,
            $result->visibility['app_url'],
        );
        $this->assertEquals(
            TenantConfigVisibility::PUBLIC ,
            $result->visibility['app_name'],
        );
        $this->assertNull($result->capacitorScheme);
    }

    /**
     * Test that TenantConfigCast returns null when set value is null.
     *
     * @throws JsonException
     */
    public function testSetReturnsNullWhenValueIsNull(): void
    {
        $cast  = new TenantConfigCast();
        $model = new Tenant();

        $result = $cast->set($model, 'config', null, []);
        $this->assertNull($result);
    }

    /**
     * Test that TenantConfigCast correctly casts TenantConfig object to JSON string.
     *
     * @throws JsonException
     */
    public function testSetCastsTenantConfigToJson(): void
    {
        $cast  = new TenantConfigCast();
        $model = new Tenant();

        $config = $this->createTenantConfig();

        $result = $cast->set($model, 'config', $config, []);

        $this->assertIsString($result);

        $decodedResult = json_decode($result, true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('https://api.example.com', $decodedResult['app_url']);
        $this->assertEquals('https://app.example.com', $decodedResult['frontend_url']);
        $this->assertEquals('Example App', $decodedResult['app_name']);
        $this->assertEquals('testing', $decodedResult['app_env']);
        $this->assertEquals('https://internal-api.example.com', $decodedResult['internal_api_url']);
        $this->assertTrue($decodedResult['app_debug']);
        $this->assertEquals('Example', $decodedResult['mail_from_name']);
        $this->assertEquals('no-reply@example.com', $decodedResult['mail_from_address']);
        $this->assertCount(2, $decodedResult['__visibility']);
        $this->assertEquals(TenantConfigVisibility::PUBLIC ->value, $decodedResult['__visibility']['app_url']);
        $this->assertEquals(TenantConfigVisibility::PUBLIC ->value, $decodedResult['__visibility']['app_name']);
    }

    /**
     * Test that TenantConfigCast correctly casts array to JSON string.
     *
     * @throws JsonException
     */
    public function testSetCastsArrayToJson(): void
    {
        $cast  = new TenantConfigCast();
        $model = new Tenant();

        $configArray = [
            'frontend_url'      => 'https://app.example.com',
            'app_url'           => 'https://api.example.com',
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
        ];

        $result = $cast->set($model, 'config', $configArray, []);

        $this->assertIsString($result);

        $decodedResult = json_decode($result, true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('https://api.example.com', $decodedResult['app_url']);
        $this->assertEquals('https://app.example.com', $decodedResult['frontend_url']);
        $this->assertEquals('Example App', $decodedResult['app_name']);
        $this->assertEquals('testing', $decodedResult['app_env']);
        $this->assertEquals('https://internal-api.example.com', $decodedResult['internal_api_url']);
        $this->assertTrue($decodedResult['app_debug']);
        $this->assertEquals('Example', $decodedResult['mail_from_name']);
        $this->assertEquals('no-reply@example.com', $decodedResult['mail_from_address']);
        $this->assertCount(2, $decodedResult['__visibility']);
    }
}
