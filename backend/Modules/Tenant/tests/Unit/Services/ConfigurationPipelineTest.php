<?php

namespace Modules\Tenant\Tests\Unit\Services;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Collection;
use Modules\Tenant\Contracts\ConfigurationPipeInterface;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Services\ConfigurationPipeline;
use Modules\Tenant\ValueObjects\DynamicTenantConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

#[CoversClass(ConfigurationPipeline::class)]
#[Group('tenant-module')]
#[Group('tenant-services')]
final class ConfigurationPipelineTest extends TestCase
{
    private ConfigurationPipeline $pipeline;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pipeline = new ConfigurationPipeline();
    }

    #[TestDox('Should initialize with empty pipes collection')]
    public function testInitializesWithEmptyPipes(): void
    {
        $pipes = $this->pipeline->getPipes();

        $this->assertInstanceOf(Collection::class, $pipes);
        $this->assertTrue($pipes->isEmpty());
    }

    #[TestDox('Should register pipe instance')]
    public function testRegisterPipeInstance(): void
    {
        $pipe = $this->createMock(ConfigurationPipeInterface::class);

        $result = $this->pipeline->register($pipe);

        $this->assertSame($this->pipeline, $result);
        $this->assertCount(1, $this->pipeline->getPipes());
        $this->assertTrue($this->pipeline->getPipes()->contains($pipe));
    }

    #[TestDox('Should register pipe by class name')]
    public function testRegisterPipeByClassName(): void
    {
        $pipe      = $this->createMock(ConfigurationPipeInterface::class);
        $pipeClass = get_class($pipe);

        $this->app->instance($pipeClass, $pipe);

        $result = $this->pipeline->register($pipeClass);

        $this->assertSame($this->pipeline, $result);
        $this->assertCount(1, $this->pipeline->getPipes());
        $this->assertTrue($this->pipeline->getPipes()->contains($pipe));
    }

    #[TestDox('Should register multiple pipes')]
    public function testRegisterManyPipes(): void
    {
        $pipe1      = $this->createMock(ConfigurationPipeInterface::class);
        $pipe2      = $this->createMock(ConfigurationPipeInterface::class);
        $pipe3Class = 'TestPipeClass';
        $pipe3      = $this->createMock(ConfigurationPipeInterface::class);

        $this->app->instance($pipe3Class, $pipe3);

        $result = $this->pipeline->registerMany([$pipe1, $pipe2, $pipe3Class]);

        $this->assertSame($this->pipeline, $result);
        $this->assertCount(3, $this->pipeline->getPipes());
        $this->assertTrue($this->pipeline->getPipes()->contains($pipe1));
        $this->assertTrue($this->pipeline->getPipes()->contains($pipe2));
        $this->assertTrue($this->pipeline->getPipes()->contains($pipe3));
    }

    #[TestDox('Should apply pipeline when tenant has no effective config')]
    public function testApplyWithNoEffectiveConfig(): void
    {
        $tenant = $this->createMock(Tenant::class);
        $tenant->method('getEffectiveConfig')->willReturn(null);

        $config = $this->createMock(ConfigRepository::class);

        // Should return early without processing
        $this->pipeline->apply($tenant, $config);

        $this->assertTrue(true); // Test passes if no exception thrown
    }

    #[TestDox('Should apply pipeline with DynamicTenantConfig')]
    public function testApplyWithDynamicTenantConfig(): void
    {
        $tenant       = $this->createMock(Tenant::class);
        $tenantConfig = new DynamicTenantConfig();
        $tenantConfig->set('test_key', 'test_value');
        $tenant->method('getEffectiveConfig')->willReturn($tenantConfig);

        $config = $this->createMock(ConfigRepository::class);

        $pipe = $this->createMock(ConfigurationPipeInterface::class);
        $pipe->expects($this->once())
            ->method('handle')
            ->with(
                $this->equalTo($tenant),
                $this->equalTo($config),
                $this->equalTo(['test_key' => 'test_value']),
                $this->isInstanceOf(\Closure::class),
            )
            ->willReturnCallback(function ($tenant, $config, $tenantConfig, $next) {
                return $next(['tenant' => $tenant, 'config' => $config, 'tenantConfig' => $tenantConfig]);
            });

        $this->pipeline->register($pipe);

        $this->pipeline->apply($tenant, $config);
    }

    #[TestDox('Should apply pipeline with array config')]
    public function testApplyWithArrayConfig(): void
    {
        $tenant = $this->createMock(Tenant::class);

        // Create a mock that has toArray method and extends DynamicTenantConfig
        $tenantConfig = new class () extends DynamicTenantConfig {
            public function toArray(): array
            {
                return ['config' => ['array_key' => 'array_value']];
            }
        };

        $tenant->method('getEffectiveConfig')->willReturn($tenantConfig);

        $config = $this->createMock(ConfigRepository::class);

        $pipe = $this->createMock(ConfigurationPipeInterface::class);
        $pipe->expects($this->once())
            ->method('handle')
            ->with(
                $this->equalTo($tenant),
                $this->equalTo($config),
                $this->equalTo(['array_key' => 'array_value']),
                $this->isInstanceOf(\Closure::class),
            )
            ->willReturnCallback(function ($tenant, $config, $tenantConfig, $next) {
                return $next(['tenant' => $tenant, 'config' => $config, 'tenantConfig' => $tenantConfig]);
            });

        $this->pipeline->register($pipe);

        $this->pipeline->apply($tenant, $config);
    }

    #[TestDox('Should handle pipe that throws exception')]
    public function testApplyWithPipeThatThrowsException(): void
    {
        $tenant       = $this->createMock(Tenant::class);
        $tenantConfig = new DynamicTenantConfig();
        $tenant->method('getEffectiveConfig')->willReturn($tenantConfig);

        $config = $this->createMock(ConfigRepository::class);

        $pipe = $this->createMock(ConfigurationPipeInterface::class);
        $pipe->method('handle')->willThrowException(new \Exception('Pipe error'));

        $this->pipeline->register($pipe);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Pipe error');

        $this->pipeline->apply($tenant, $config);
    }

    #[TestDox('Should handle multiple pipes with same priority')]
    public function testApplyWithSamePriorityPipes(): void
    {
        $tenant       = $this->createMock(Tenant::class);
        $tenantConfig = new DynamicTenantConfig();
        $tenant->method('getEffectiveConfig')->willReturn($tenantConfig);

        $config = $this->createMock(ConfigRepository::class);

        $executionOrder = [];

        $pipe1 = $this->createMock(ConfigurationPipeInterface::class);
        $pipe1->method('handle')->willReturnCallback(function ($tenant, $config, $tenantConfig, $next) use (&$executionOrder) {
            $executionOrder[] = 'pipe1';
            return $next(['tenant' => $tenant, 'config' => $config, 'tenantConfig' => $tenantConfig]);
        });

        $pipe2 = $this->createMock(ConfigurationPipeInterface::class);
        $pipe2->method('handle')->willReturnCallback(function ($tenant, $config, $tenantConfig, $next) use (&$executionOrder) {
            $executionOrder[] = 'pipe2';
            return $next(['tenant' => $tenant, 'config' => $config, 'tenantConfig' => $tenantConfig]);
        });

        $this->pipeline->register($pipe1);
        $this->pipeline->register($pipe2);

        $this->pipeline->apply($tenant, $config);

        // Both pipes should execute (order may vary with same priority)
        $this->assertContains('pipe1', $executionOrder);
        $this->assertContains('pipe2', $executionOrder);
        $this->assertCount(2, $executionOrder);
    }

    #[TestDox('Should handle pipe that modifies config')]
    public function testApplyWithConfigModifyingPipe(): void
    {
        $tenant       = $this->createMock(Tenant::class);
        $tenantConfig = new DynamicTenantConfig();
        $tenantConfig->set('original_key', 'original_value');
        $tenant->method('getEffectiveConfig')->willReturn($tenantConfig);

        $config = $this->createMock(ConfigRepository::class);
        $config->expects($this->once())
            ->method('set')
            ->with('modified_key', 'modified_value');

        $pipe = $this->createMock(ConfigurationPipeInterface::class);
        $pipe->method('handle')->willReturnCallback(function ($tenant, $config, $tenantConfig, $next) {
            // Simulate pipe modifying config
            $config->set('modified_key', 'modified_value');
            return $next(['tenant' => $tenant, 'config' => $config, 'tenantConfig' => $tenantConfig]);
        });

        $this->pipeline->register($pipe);

        $this->pipeline->apply($tenant, $config);
    }

    #[TestDox('Should preserve pipe registration order within same priority')]
    public function testPipeRegistrationOrderWithinSamePriority(): void
    {
        $pipe1 = $this->createMock(ConfigurationPipeInterface::class);
        $pipe2 = $this->createMock(ConfigurationPipeInterface::class);
        $pipe3 = $this->createMock(ConfigurationPipeInterface::class);

        $this->pipeline->register($pipe1);
        $this->pipeline->register($pipe2);
        $this->pipeline->register($pipe3);

        $pipes = $this->pipeline->getPipes();

        $this->assertSame($pipe1, $pipes->get(0));
        $this->assertSame($pipe2, $pipes->get(1));
        $this->assertSame($pipe3, $pipes->get(2));
    }

    #[TestDox('Should resolve configuration from array with no pipes')]
    public function testResolveFromArrayWithNoPipes(): void
    {
        $tenant = new Tenant();
        $tenant->public_id = 'tenant-123';
        $tenant->name = 'Test Tenant';
        $tenant->parent = null;

        $configArray = ['test_key' => 'test_value'];

        $result = $this->pipeline->resolveFromArray($tenant, $configArray);

        $this->assertArrayHasKey('values', $result);
        $this->assertArrayHasKey('visibility', $result);

        // Should include tenant identity
        $this->assertEquals('tenant-123', $result['values']['tenantId']);
        $this->assertEquals('Test Tenant', $result['values']['tenantName']);
        $this->assertEquals('public', $result['visibility']['tenantId']);
        $this->assertEquals('public', $result['visibility']['tenantName']);
    }

    #[TestDox('Should resolve configuration from array with single pipe')]
    public function testResolveFromArrayWithSinglePipe(): void
    {
        $tenant = new Tenant();
        $tenant->public_id = 'tenant-456';
        $tenant->name = 'Single Pipe Tenant';
        $tenant->parent = null;

        $configArray = ['config_key' => 'config_value'];

        $pipe = $this->createMock(ConfigurationPipeInterface::class);
        $pipe->expects($this->once())
            ->method('resolve')
            ->with($tenant, $configArray)
            ->willReturn([
                'values' => ['pipe_key' => 'pipe_value'],
                'visibility' => ['pipe_key' => 'private']
            ]);

        $this->pipeline->register($pipe);

        $result = $this->pipeline->resolveFromArray($tenant, $configArray);

        $this->assertEquals('pipe_value', $result['values']['pipe_key']);
        $this->assertEquals('private', $result['visibility']['pipe_key']);
        $this->assertEquals('tenant-456', $result['values']['tenantId']);
        $this->assertEquals('Single Pipe Tenant', $result['values']['tenantName']);
    }

    #[TestDox('Should resolve configuration with parent tenant identity')]
    public function testResolveFromArrayWithParentTenant(): void
    {
        $parentTenant = new Tenant();
        $parentTenant->public_id = 'parent-123';
        $parentTenant->name = 'Parent Tenant';

        $tenant = new Tenant();
        $tenant->public_id = 'child-456';
        $tenant->name = 'Child Tenant';
        $tenant->parent = $parentTenant;

        $configArray = [];

        $result = $this->pipeline->resolveFromArray($tenant, $configArray);

        // Should use parent tenant for identity
        $this->assertEquals('parent-123', $result['values']['tenantId']);
        $this->assertEquals('Parent Tenant', $result['values']['tenantName']);
        $this->assertEquals('public', $result['visibility']['tenantId']);
        $this->assertEquals('public', $result['visibility']['tenantName']);
    }

    #[TestDox('Should handle pipe that returns only values')]
    public function testResolveFromArrayWithPipeReturningOnlyValues(): void
    {
        $tenant = new Tenant();
        $tenant->public_id = 'tenant-values-only';
        $tenant->name = 'Values Only Tenant';
        $tenant->parent = null;

        $configArray = [];

        $pipe = $this->createMock(ConfigurationPipeInterface::class);
        $pipe->method('resolve')->willReturn([
            'values' => ['values_only_key' => 'values_only_value']
            // No visibility key
        ]);

        $this->pipeline->register($pipe);

        $result = $this->pipeline->resolveFromArray($tenant, $configArray);

        $this->assertEquals('values_only_value', $result['values']['values_only_key']);
        $this->assertArrayNotHasKey('values_only_key', $result['visibility']);
    }

    #[TestDox('Should handle pipe that returns only visibility')]
    public function testResolveFromArrayWithPipeReturningOnlyVisibility(): void
    {
        $tenant = new Tenant();
        $tenant->public_id = 'tenant-visibility-only';
        $tenant->name = 'Visibility Only Tenant';
        $tenant->parent = null;

        $configArray = [];

        $pipe = $this->createMock(ConfigurationPipeInterface::class);
        $pipe->method('resolve')->willReturn([
            'visibility' => ['visibility_only_key' => 'private']
            // No values key
        ]);

        $this->pipeline->register($pipe);

        $result = $this->pipeline->resolveFromArray($tenant, $configArray);

        $this->assertEquals('private', $result['visibility']['visibility_only_key']);
        $this->assertArrayNotHasKey('visibility_only_key', $result['values']);
    }

    #[TestDox('Should handle pipe that returns empty result')]
    public function testResolveFromArrayWithPipeReturningEmptyResult(): void
    {
        $tenant = new Tenant();
        $tenant->public_id = 'tenant-empty';
        $tenant->name = 'Empty Result Tenant';
        $tenant->parent = null;

        $configArray = [];

        $pipe = $this->createMock(ConfigurationPipeInterface::class);
        $pipe->method('resolve')->willReturn([]);

        $this->pipeline->register($pipe);

        $result = $this->pipeline->resolveFromArray($tenant, $configArray);

        // Should only have tenant identity
        $this->assertEquals(['tenantId' => 'tenant-empty', 'tenantName' => 'Empty Result Tenant'], $result['values']);
        $this->assertEquals(['tenantId' => 'public', 'tenantName' => 'public'], $result['visibility']);
    }
}
