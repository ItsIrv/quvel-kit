<?php

namespace Modules\Auth\Tests\Unit\Providers;

use Illuminate\Foundation\Application;
use Modules\Auth\Providers\AuthServiceProvider;
use Modules\Auth\Providers\RouteServiceProvider;
use Modules\Auth\Services\ClientNonceService;
use Modules\Auth\Services\HmacService;
use Modules\Auth\Services\NonceSessionService;
use Modules\Auth\Services\ServerTokenService;
use Modules\Auth\Services\SocialiteService;
use Modules\Auth\Services\UserAuthenticationService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

#[CoversClass(AuthServiceProvider::class)]
#[Group('auth-module')]
#[Group('auth-providers')]
class AuthServiceProviderTest extends TestCase
{
    private AuthServiceProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new AuthServiceProvider($this->app);
    }

    #[TestDox('registers services correctly')]
    public function testRegistersServices(): void
    {
        $app = $this->createMock(Application::class);

        $singletons = [];
        $registers  = [];

        $app->method('singleton')
            ->willReturnCallback(function ($class) use (&$singletons): void {
                $singletons[] = strtolower($class);
            });

        $app->method('scoped')
            ->willReturnCallback(function ($class) use (&$singletons): void {
                $singletons[] = strtolower($class);
            });

        $app->method('register')
            ->willReturnCallback(function ($class) use (&$registers): void {
                $registers[] = strtolower($class);
            });

        $provider = new AuthServiceProvider($app);
        $provider->register();

        $expectedSingletons = [
            strtolower(HmacService::class),
            strtolower(ClientNonceService::class),
            strtolower(ServerTokenService::class),
            strtolower(UserAuthenticationService::class),
            strtolower(NonceSessionService::class),
            strtolower(SocialiteService::class),
        ];

        $expectedRegisters = [
            strtolower(RouteServiceProvider::class),
        ];

        $this->assertEqualsCanonicalizing($expectedSingletons, $singletons);
        $this->assertEqualsCanonicalizing($expectedRegisters, $registers);
    }

    #[TestDox('tenant config file exists and has correct structure')]
    public function testTenantConfigFileExistsAndHasCorrectStructure(): void
    {
        $configPath = __DIR__ . '/../../../config/tenant.php';
        $this->assertFileExists($configPath, 'Tenant config file should exist');

        $config = require $configPath;
        $this->assertIsArray($config, 'Config should return an array');

        // Verify structure
        $this->assertArrayHasKey('seeders', $config);
        $this->assertArrayHasKey('pipes', $config);

        // Verify templates exist
        $this->assertArrayHasKey('basic', $config['seeders']);
        $this->assertArrayHasKey('isolated', $config['seeders']);

        // Verify pipe registration
        $this->assertContains(\Modules\Auth\Pipes\AuthConfigPipe::class, $config['pipes']);
    }

    #[TestDox('config seeder callable generates correct configuration for isolated template')]
    public function testConfigSeederCallableGeneratesCorrectConfigurationForIsolatedTemplate(): void
    {
        $configPath = __DIR__ . '/../../../config/tenant.php';
        $config = require $configPath;

        $isolatedSeederClass = $config['seeders']['isolated'];
        $this->assertIsString($isolatedSeederClass);

        $isolatedSeeder = new $isolatedSeederClass();
        $this->assertInstanceOf(\Modules\Tenant\Contracts\TenantConfigSeederInterface::class, $isolatedSeeder);

        // Test with cache prefix
        $inputConfig = ['cache_prefix' => 'tenant_abc123_'];
        $result = $isolatedSeeder->getConfig('isolated', $inputConfig);

        // Verify structure and expected keys
        $this->assertEquals(['google', 'microsoft'], $result['socialite_providers']);
        $this->assertEquals(240, $result['session_lifetime']);
        $this->assertArrayHasKey('oauth_credentials', $result);
        $this->assertArrayHasKey('google', $result['oauth_credentials']);
        $this->assertArrayHasKey('microsoft', $result['oauth_credentials']);
        $this->assertArrayHasKey('client_id', $result['oauth_credentials']['google']);
        $this->assertArrayHasKey('client_secret', $result['oauth_credentials']['google']);

        // Test with different input config
        $inputConfig2 = ['cache_prefix' => 'different_prefix_'];
        $result2 = $isolatedSeeder->getConfig('isolated', $inputConfig2);

        // Should return same configuration regardless of input for this seeder
        $this->assertEquals(['google', 'microsoft'], $result2['socialite_providers']);
        $this->assertEquals(240, $result2['session_lifetime']);
    }


    #[TestDox('visibility configuration is consistent')]
    public function testVisibilityConfigurationIsConsistent(): void
    {
        $configPath = __DIR__ . '/../../../config/tenant.php';
        $config = require $configPath;

        // Test basic visibility
        $basicSeederClass = $config['seeders']['basic'];
        $basicSeeder = new $basicSeederClass();
        $basicVisibility = $basicSeeder->getVisibility();
        $this->assertEquals('public', $basicVisibility['socialite_providers']);

        // Test isolated visibility
        $isolatedSeederClass = $config['seeders']['isolated'];
        $isolatedSeeder = new $isolatedSeederClass();
        $isolatedVisibility = $isolatedSeeder->getVisibility();
        $this->assertEquals('public', $isolatedVisibility['socialite_providers']);
        $this->assertEquals('private', $isolatedVisibility['oauth_credentials']);
        $this->assertEquals('protected', $isolatedVisibility['session_lifetime']);
    }

    #[TestDox('regex pattern correctly extracts tenant ID from cache prefix')]
    #[DataProvider('regexPatternProvider')]
    public function testRegexPatternCorrectlyExtractsTenantIdFromCachePrefix(
        string $cachePrefix,
        ?string $expectedTenantId,
    ): void {
        $matches = [];
        $result  = preg_match('/tenant_([a-z0-9]+)_?/i', $cachePrefix, $matches);

        if ($expectedTenantId !== null) {
            $this->assertEquals(1, $result);
            $this->assertEquals($expectedTenantId, $matches[1]);
        } else {
            $this->assertEquals(0, $result);
        }
    }

    public static function regexPatternProvider(): array
    {
        return [
            ['tenant_abc123_', 'abc123'],
            ['tenant_XYZ789_', 'XYZ789'],
            ['tenant_123abc456def_', '123abc456def'],
            ['tenant_a1b2c3_cache', 'a1b2c3'],
            ['TENANT_test123_', 'test123'],
            ['invalid_format', null],
            ['completely_different', null],
        ];
    }
}
