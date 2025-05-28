<?php

namespace Modules\Tenant\Services;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Collection;
use Modules\Tenant\Contracts\ConfigurationPipeInterface;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\ValueObjects\DynamicTenantConfig;

/**
 * Manages the configuration pipeline for applying tenant configurations.
 */
class ConfigurationPipeline
{
    /**
     * The registered configuration pipes.
     *
     * @var Collection<int, ConfigurationPipeInterface>
     */
    protected Collection $pipes;

    /**
     * Create a new configuration pipeline.
     */
    public function __construct()
    {
        $this->pipes = collect();
    }

    /**
     * Register a configuration pipe.
     *
     * @param ConfigurationPipeInterface|string $pipe
     * @return static
     */
    public function register(ConfigurationPipeInterface|string $pipe): static
    {
        if (is_string($pipe)) {
            $pipe = app($pipe);
        }

        $this->pipes->push($pipe);

        return $this;
    }

    /**
     * Register multiple configuration pipes.
     *
     * @param array<ConfigurationPipeInterface|string> $pipes
     * @return static
     */
    public function registerMany(array $pipes): static
    {
        foreach ($pipes as $pipe) {
            $this->register($pipe);
        }

        return $this;
    }

    /**
     * Apply the configuration pipeline for a tenant.
     *
     * @param Tenant $tenant
     * @param ConfigRepository $config
     * @return void
     */
    public function apply(Tenant $tenant, ConfigRepository $config): void
    {
        $tenantConfig = $tenant->getEffectiveConfig();

        if (!$tenantConfig) {
            return;
        }

        // Convert to array for pipeline processing
        $configArray = $tenantConfig instanceof DynamicTenantConfig
            ? $tenantConfig->toArray()['config']
            : $tenantConfig->toArray();

        // Sort pipes by priority (higher priority first)
        $sortedPipes = $this->pipes
            ->sortByDesc(fn (ConfigurationPipeInterface $pipe) => $pipe->priority())
            ->map(fn (ConfigurationPipeInterface $pipe) => function ($passable, $next) use ($pipe) {
                return $pipe->handle(
                    $passable['tenant'],
                    $passable['config'],
                    $passable['tenantConfig'],
                    $next,
                );
            })
            ->toArray();

        // Run the pipeline
        app(Pipeline::class)
            ->send([
                'tenant'       => $tenant,
                'config'       => $config,
                'tenantConfig' => $configArray,
            ])
            ->through($sortedPipes)
            ->thenReturn();
    }

    /**
     * Get all registered pipes.
     *
     * @return Collection<int, ConfigurationPipeInterface>
     */
    public function getPipes(): Collection
    {
        return $this->pipes;
    }

    /**
     * Get documentation for all registered pipes.
     *
     * @return array<string, array<string>>
     */
    public function getDocumentation(): array
    {
        $docs = [];

        foreach ($this->pipes as $pipe) {
            $docs[get_class($pipe)] = $pipe->handles();
        }

        return $docs;
    }
}
