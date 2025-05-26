<?php

namespace Modules\Tenant\Tests\Unit\Services;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Mockery;
use Mockery\MockInterface;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Services\ConfigApplier;
use Modules\Tenant\Services\ConfigurationPipeline;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

#[CoversClass(ConfigApplier::class)]
#[Group('tenant-module')]
#[Group('tenant-services')]
final class ConfigApplierTest extends TestCase
{
    /**
     * @var Tenant|MockInterface
     */
    protected Tenant $tenant;

    /**
     * @var ConfigRepository|MockInterface
     */
    protected ConfigRepository $config;

    /**
     * @var ConfigurationPipeline|MockInterface
     */
    protected ConfigurationPipeline $configPipeline;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Mockery::mock(Tenant::class);
        $this->config = Mockery::mock(ConfigRepository::class);
        $this->configPipeline = Mockery::mock(ConfigurationPipeline::class);
        
        // Replace the ConfigurationPipeline instance in the container
        $this->app->instance(ConfigurationPipeline::class, $this->configPipeline);
    }

    #[TestDox('It should delegate to ConfigurationPipeline')]
    public function testDelegatesToConfigurationPipeline(): void
    {
        // Arrange
        $this->configPipeline->shouldReceive('apply')
            ->once()
            ->with($this->tenant, $this->config);

        // Act
        ConfigApplier::apply($this->tenant, $this->config);

        // Assert - Mockery will verify the expectation
    }
}