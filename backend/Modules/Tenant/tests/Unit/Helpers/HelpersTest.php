<?php

namespace Modules\Tenant\Tests\Unit\Helpers;

use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Enums\TenantConfigVisibility;
use Modules\Tenant\Exceptions\TenantNotFoundException;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Services\ConfigurationPipeline;
use Modules\Tenant\Services\FindService;
use Modules\Tenant\ValueObjects\DynamicTenantConfig;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase as BaseTestCase;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

#[Group('tenant-module')]
#[Group('tenant-helpers')]
class HelpersTest extends BaseTestCase
{
    private Tenant $testTenant;
    private FindService $findService;
    private TenantContext $mockTenantContext;
    private ConfigurationPipeline $configPipeline;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testTenant = Tenant::factory()->make([
            'id' => 123,
            'domain' => 'test.example.com',
            'config' => new DynamicTenantConfig(['test_key' => 'test_value'])
        ]);

        $this->findService = $this->createMock(FindService::class);
        $this->mockTenantContext = $this->createMock(TenantContext::class);
        $this->configPipeline = $this->createMock(ConfigurationPipeline::class);

        $this->app->instance(FindService::class, $this->findService);
        $this->app->instance(TenantContext::class, $this->mockTenantContext);
        $this->app->instance(ConfigurationPipeline::class, $this->configPipeline);
    }

    /**
     * Test setTenant function with integer ID.
     */
    public function testSetTenantWithIntegerId(): void
    {
        $this->findService->expects($this->once())
            ->method('findById')
            ->with(123)
            ->willReturn($this->testTenant);

        $this->mockTenantContext->expects($this->once())
            ->method('set')
            ->with($this->testTenant);

        $this->configPipeline->expects($this->once())
            ->method('apply')
            ->with($this->anything(), $this->isInstanceOf(ConfigRepository::class));

        $result = setTenant(123);
        $this->assertTrue($result);
    }

    /**
     * Test setTenant function with string domain.
     */
    public function testSetTenantWithStringDomain(): void
    {
        $this->findService->expects($this->once())
            ->method('findTenantByDomain')
            ->with('test.example.com')
            ->willReturn($this->testTenant);

        $this->mockTenantContext->expects($this->once())
            ->method('set')
            ->with($this->testTenant);

        $this->configPipeline->expects($this->once())
            ->method('apply')
            ->with($this->anything(), $this->isInstanceOf(ConfigRepository::class));

        $result = setTenant('test.example.com');
        $this->assertTrue($result);
    }

    /**
     * Test setTenant function with Tenant instance.
     */
    public function testSetTenantWithTenantInstance(): void
    {
        $this->mockTenantContext->expects($this->once())
            ->method('set')
            ->with($this->testTenant);

        $this->configPipeline->expects($this->once())
            ->method('apply')
            ->with($this->anything(), $this->isInstanceOf(ConfigRepository::class));

        $result = setTenant($this->testTenant);
        $this->assertTrue($result);
    }

    /**
     * Test setTenant function with invalid type throws exception.
     */
    public function testSetTenantWithInvalidTypeThrowsException(): void
    {
        $this->expectException(TenantNotFoundException::class);
        $this->expectExceptionMessage('Tenant not found.');

        setTenant(['invalid' => 'array']);
    }

    /**
     * Test setTenant function when tenant not found by ID throws exception.
     */
    public function testSetTenantWithUnfoundIdThrowsException(): void
    {
        $this->findService->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->expectException(TenantNotFoundException::class);
        $this->expectExceptionMessage('Tenant not found');

        setTenant(999);
    }

    /**
     * Test setTenant function when tenant not found by domain throws exception.
     */
    public function testSetTenantWithUnfoundDomainThrowsException(): void
    {
        $this->findService->expects($this->once())
            ->method('findTenantByDomain')
            ->with('nonexistent.example.com')
            ->willReturn(null);

        $this->expectException(TenantNotFoundException::class);
        $this->expectExceptionMessage('Tenant not found');

        setTenant('nonexistent.example.com');
    }

    /**
     * Test setTenantContext function with integer ID.
     */
    public function testSetTenantContextWithIntegerId(): void
    {
        $this->findService->expects($this->once())
            ->method('findById')
            ->with(456)
            ->willReturn($this->testTenant);

        $this->mockTenantContext->expects($this->once())
            ->method('set')
            ->with($this->testTenant);

        // Should NOT call ConfigurationPipeline
        $this->configPipeline->expects($this->never())
            ->method('apply');

        $result = setTenantContext(456);
        $this->assertTrue($result);
    }

    /**
     * Test setTenantContext function with string domain.
     */
    public function testSetTenantContextWithStringDomain(): void
    {
        $this->findService->expects($this->once())
            ->method('findTenantByDomain')
            ->with('context.example.com')
            ->willReturn($this->testTenant);

        $this->mockTenantContext->expects($this->once())
            ->method('set')
            ->with($this->testTenant);

        $this->configPipeline->expects($this->never())
            ->method('apply');

        $result = setTenantContext('context.example.com');
        $this->assertTrue($result);
    }

    /**
     * Test setTenantContext function with Tenant instance.
     */
    public function testSetTenantContextWithTenantInstance(): void
    {
        $this->mockTenantContext->expects($this->once())
            ->method('set')
            ->with($this->testTenant);

        $this->configPipeline->expects($this->never())
            ->method('apply');

        $result = setTenantContext($this->testTenant);
        $this->assertTrue($result);
    }

    /**
     * Test setTenantContext function with invalid type throws exception.
     */
    public function testSetTenantContextWithInvalidTypeThrowsException(): void
    {
        $this->expectException(TenantNotFoundException::class);
        $this->expectExceptionMessage('Tenant not found.');

        setTenantContext(null);
    }

    /**
     * Test getTenant function returns current tenant.
     */
    public function testGetTenantReturnsCurrentTenant(): void
    {
        $this->mockTenantContext->expects($this->once())
            ->method('get')
            ->willReturn($this->testTenant);

        $result = getTenant();
        $this->assertSame($this->testTenant, $result);
    }

    /**
     * Test getTenantConfig function returns complete config when no key specified.
     */
    public function testGetTenantConfigReturnsCompleteConfig(): void
    {
        $config = new DynamicTenantConfig(['key1' => 'value1', 'key2' => 'value2']);
        $tenant = $this->createMock(Tenant::class);
        $tenant->expects($this->once())
            ->method('getEffectiveConfig')
            ->willReturn($config);

        $this->mockTenantContext->expects($this->once())
            ->method('get')
            ->willReturn($tenant);

        $result = getTenantConfig();
        $this->assertSame($config, $result);
    }

    /**
     * Test getTenantConfig function returns specific config value when key specified.
     */
    public function testGetTenantConfigReturnsSpecificValue(): void
    {
        $config = $this->createMock(DynamicTenantConfig::class);
        $config->expects($this->once())
            ->method('get')
            ->with('specific_key', 'default_value')
            ->willReturn('specific_value');

        $tenant = $this->createMock(Tenant::class);
        $tenant->expects($this->once())
            ->method('getEffectiveConfig')
            ->willReturn($config);

        $this->mockTenantContext->expects($this->once())
            ->method('get')
            ->willReturn($tenant);

        $result = getTenantConfig('specific_key', 'default_value');
        $this->assertEquals('specific_value', $result);
    }

    /**
     * Test setTenantConfig function sets configuration value.
     */
    public function testSetTenantConfigSetsConfigurationValue(): void
    {
        $config = new DynamicTenantConfig();
        $tenant = new Tenant();
        $tenant->config = $config;

        $this->mockTenantContext->expects($this->once())
            ->method('get')
            ->willReturn($tenant);

        setTenantConfig('new_key', 'new_value');

        $this->assertEquals('new_value', $config->get('new_key'));
        $this->assertSame($config, $tenant->config);
    }

    /**
     * Test setTenantConfig function with visibility enum.
     */
    public function testSetTenantConfigWithVisibilityEnum(): void
    {
        $config = new DynamicTenantConfig();
        $tenant = new Tenant();
        $tenant->config = $config;

        $this->mockTenantContext->expects($this->once())
            ->method('get')
            ->willReturn($tenant);

        setTenantConfig('public_key', 'public_value', TenantConfigVisibility::PUBLIC);

        $this->assertEquals('public_value', $config->get('public_key'));
        $this->assertEquals(TenantConfigVisibility::PUBLIC, $config->getVisibility('public_key'));
    }

    /**
     * Test setTenantConfig function with visibility string.
     */
    public function testSetTenantConfigWithVisibilityString(): void
    {
        $config = new DynamicTenantConfig();
        $tenant = new Tenant();
        $tenant->config = $config;

        $this->mockTenantContext->expects($this->once())
            ->method('get')
            ->willReturn($tenant);

        setTenantConfig('protected_key', 'protected_value', 'protected');

        $this->assertEquals('protected_value', $config->get('protected_key'));
        $this->assertEquals(TenantConfigVisibility::PROTECTED, $config->getVisibility('protected_key'));
    }

    /**
     * Test setTenantConfig function creates new config when none exists.
     */
    public function testSetTenantConfigCreatesNewConfigWhenNoneExists(): void
    {
        $tenant = new Tenant();
        $tenant->config = null;

        $this->mockTenantContext->expects($this->once())
            ->method('get')
            ->willReturn($tenant);

        setTenantConfig('first_key', 'first_value');

        $this->assertInstanceOf(DynamicTenantConfig::class, $tenant->config);
        $this->assertEquals('first_value', $tenant->config->get('first_key'));
    }

    /**
     * Test createTenantConfig function creates new config instance.
     */
    public function testCreateTenantConfigCreatesNewInstance(): void
    {
        $data = ['key1' => 'value1', 'key2' => 'value2'];
        $visibility = ['key1' => 'public', 'key2' => 'private'];

        $result = createTenantConfig($data, $visibility);

        $this->assertInstanceOf(DynamicTenantConfig::class, $result);
        $this->assertEquals('value1', $result->get('key1'));
        $this->assertEquals('value2', $result->get('key2'));
    }

    /**
     * Test createTenantConfig function with empty parameters.
     */
    public function testCreateTenantConfigWithEmptyParameters(): void
    {
        $result = createTenantConfig();

        $this->assertInstanceOf(DynamicTenantConfig::class, $result);
        $this->assertNull($result->get('nonexistent_key'));
    }
}
