<?php

namespace Modules\Tenant\Tests\Unit\Services;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Pipeline\Pipeline;
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
        $pipe = $this->createMock(ConfigurationPipeInterface::class);
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
        $pipe1 = $this->createMock(ConfigurationPipeInterface::class);
        $pipe2 = $this->createMock(ConfigurationPipeInterface::class);
        $pipe3Class = 'TestPipeClass';
        $pipe3 = $this->createMock(ConfigurationPipeInterface::class);

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
        $tenant = $this->createMock(Tenant::class);
        $tenantConfig = new DynamicTenantConfig();
        $tenantConfig->set('test_key', 'test_value');
        $tenant->method('getEffectiveConfig')->willReturn($tenantConfig);

        $config = $this->createMock(ConfigRepository::class);

        $pipe = $this->createMock(ConfigurationPipeInterface::class);
        $pipe->method('priority')->willReturn(10);
        $pipe->expects($this->once())
            ->method('handle')
            ->with(
                $this->equalTo($tenant),
                $this->equalTo($config),
                $this->equalTo(['test_key' => 'test_value']),
                $this->isInstanceOf(\Closure::class)
            );

        $this->pipeline->register($pipe);

        $this->pipeline->apply($tenant, $config);
    }

    #[TestDox('Should apply pipeline with array config')]
    public function testApplyWithArrayConfig(): void
    {
        $tenant = $this->createMock(Tenant::class);
        
        // Create a mock that has toArray method and extends DynamicTenantConfig
        $tenantConfig = new class extends DynamicTenantConfig {
            public function toArray(): array
            {
                return ['config' => ['array_key' => 'array_value']];
            }
        };
        
        $tenant->method('getEffectiveConfig')->willReturn($tenantConfig);

        $config = $this->createMock(ConfigRepository::class);

        $pipe = $this->createMock(ConfigurationPipeInterface::class);
        $pipe->method('priority')->willReturn(10);
        $pipe->expects($this->once())
            ->method('handle')
            ->with(
                $this->equalTo($tenant),
                $this->equalTo($config),
                $this->equalTo(['array_key' => 'array_value']),
                $this->isInstanceOf(\Closure::class)
            );

        $this->pipeline->register($pipe);

        $this->pipeline->apply($tenant, $config);
    }

    #[TestDox('Should sort pipes by priority in descending order')]
    public function testApplySortsPipesByPriority(): void
    {
        $tenant = $this->createMock(Tenant::class);
        $tenantConfig = new DynamicTenantConfig();
        $tenant->method('getEffectiveConfig')->willReturn($tenantConfig);

        $config = $this->createMock(ConfigRepository::class);

        $executionOrder = [];

        $pipe1 = $this->createMock(ConfigurationPipeInterface::class);
        $pipe1->method('priority')->willReturn(5);
        $pipe1->method('handle')->willReturnCallback(function ($tenant, $config, $tenantConfig, $next) use (&$executionOrder) {
            $executionOrder[] = 'pipe1';
            return $next(['tenant' => $tenant, 'config' => $config, 'tenantConfig' => $tenantConfig]);
        });

        $pipe2 = $this->createMock(ConfigurationPipeInterface::class);
        $pipe2->method('priority')->willReturn(15);
        $pipe2->method('handle')->willReturnCallback(function ($tenant, $config, $tenantConfig, $next) use (&$executionOrder) {
            $executionOrder[] = 'pipe2';
            return $next(['tenant' => $tenant, 'config' => $config, 'tenantConfig' => $tenantConfig]);
        });

        $pipe3 = $this->createMock(ConfigurationPipeInterface::class);
        $pipe3->method('priority')->willReturn(10);
        $pipe3->method('handle')->willReturnCallback(function ($tenant, $config, $tenantConfig, $next) use (&$executionOrder) {
            $executionOrder[] = 'pipe3';
            return $next(['tenant' => $tenant, 'config' => $config, 'tenantConfig' => $tenantConfig]);
        });

        // Register in random order
        $this->pipeline->register($pipe1);
        $this->pipeline->register($pipe2);
        $this->pipeline->register($pipe3);

        $this->pipeline->apply($tenant, $config);

        // Should execute in priority order: pipe2 (15), pipe3 (10), pipe1 (5)
        $this->assertEquals(['pipe2', 'pipe3', 'pipe1'], $executionOrder);
    }

    #[TestDox('Should get documentation for all registered pipes')]
    public function testGetDocumentation(): void
    {
        // Create actual classes to avoid mock collision issues
        $pipe1 = new class implements ConfigurationPipeInterface {
            public function priority(): int { return 10; }
            public function handles(): array { return ['config.key1', 'config.key2']; }
            public function handle(Tenant $tenant, ConfigRepository $config, array $tenantConfig, callable $next): mixed { return $next(); }
        };

        $pipe2 = new class implements ConfigurationPipeInterface {
            public function priority(): int { return 10; }
            public function handles(): array { return ['config.key3']; }
            public function handle(Tenant $tenant, ConfigRepository $config, array $tenantConfig, callable $next): mixed { return $next(); }
        };

        $this->pipeline->register($pipe1);
        $this->pipeline->register($pipe2);

        $documentation = $this->pipeline->getDocumentation();

        $this->assertArrayHasKey(get_class($pipe1), $documentation);
        $this->assertArrayHasKey(get_class($pipe2), $documentation);
        $this->assertEquals(['config.key1', 'config.key2'], $documentation[get_class($pipe1)]);
        $this->assertEquals(['config.key3'], $documentation[get_class($pipe2)]);
    }

    #[TestDox('Should return empty documentation for no pipes')]
    public function testGetDocumentationWithNoPipes(): void
    {
        $documentation = $this->pipeline->getDocumentation();

        $this->assertIsArray($documentation);
        $this->assertEmpty($documentation);
    }

    #[TestDox('Should handle pipe that throws exception')]
    public function testApplyWithPipeThatThrowsException(): void
    {
        $tenant = $this->createMock(Tenant::class);
        $tenantConfig = new DynamicTenantConfig();
        $tenant->method('getEffectiveConfig')->willReturn($tenantConfig);

        $config = $this->createMock(ConfigRepository::class);

        $pipe = $this->createMock(ConfigurationPipeInterface::class);
        $pipe->method('priority')->willReturn(10);
        $pipe->method('handle')->willThrowException(new \Exception('Pipe error'));

        $this->pipeline->register($pipe);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Pipe error');

        $this->pipeline->apply($tenant, $config);
    }

    #[TestDox('Should handle multiple pipes with same priority')]
    public function testApplyWithSamePriorityPipes(): void
    {
        $tenant = $this->createMock(Tenant::class);
        $tenantConfig = new DynamicTenantConfig();
        $tenant->method('getEffectiveConfig')->willReturn($tenantConfig);

        $config = $this->createMock(ConfigRepository::class);

        $executionOrder = [];

        $pipe1 = $this->createMock(ConfigurationPipeInterface::class);
        $pipe1->method('priority')->willReturn(10);
        $pipe1->method('handle')->willReturnCallback(function ($tenant, $config, $tenantConfig, $next) use (&$executionOrder) {
            $executionOrder[] = 'pipe1';
            return $next(['tenant' => $tenant, 'config' => $config, 'tenantConfig' => $tenantConfig]);
        });

        $pipe2 = $this->createMock(ConfigurationPipeInterface::class);
        $pipe2->method('priority')->willReturn(10);
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
        $tenant = $this->createMock(Tenant::class);
        $tenantConfig = new DynamicTenantConfig();
        $tenantConfig->set('original_key', 'original_value');
        $tenant->method('getEffectiveConfig')->willReturn($tenantConfig);

        $config = $this->createMock(ConfigRepository::class);
        $config->expects($this->once())
            ->method('set')
            ->with('modified_key', 'modified_value');

        $pipe = $this->createMock(ConfigurationPipeInterface::class);
        $pipe->method('priority')->willReturn(10);
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
}