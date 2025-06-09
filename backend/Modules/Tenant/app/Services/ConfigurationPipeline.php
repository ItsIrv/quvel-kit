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
        // Check if tenant context is bypassed
        $tenantContext = app(\Modules\Tenant\Contexts\TenantContext::class);
        if ($tenantContext->isBypassed()) {
            return;
        }

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
     * Resolve configuration values for a tenant without side effects.
     * This returns the final configuration values that pipes would apply, including calculated defaults.
     *
     * @param Tenant $tenant
     * @return array
     */
    public function resolve(Tenant $tenant): array
    {
        // Check if tenant context is bypassed
        $tenantContext = app(\Modules\Tenant\Contexts\TenantContext::class);
        if ($tenantContext->isBypassed()) {
            return [];
        }

        $tenantConfig = $tenant->getEffectiveConfig();

        if (!$tenantConfig) {
            return [];
        }

        // Convert to array for pipeline processing
        $configArray = $tenantConfig instanceof DynamicTenantConfig
            ? $tenantConfig->toArray()['config']
            : $tenantConfig->toArray();

        return $this->resolveFromArray($tenant, $configArray);
    }

    /**
     * Resolve configuration values from a configuration array without side effects.
     * This method works on merged configuration arrays and is the core resolution logic.
     * Only returns TenantConfig interface fields for frontend consumption.
     *
     * @param Tenant $tenant The tenant context for resolution
     * @param array $configArray The merged configuration array to resolve
     * @return array
     */
    public function resolveFromArray(Tenant $tenant, array $configArray): array
    {
        // Start with empty array - only include what pipes explicitly resolve
        $resolvedConfig = [];

        // Sort pipes by priority (higher priority first)
        $sortedPipes = $this->pipes->sortByDesc(fn (ConfigurationPipeInterface $pipe) => $pipe->priority());

        // Apply each pipe's resolution (pipes should only return TenantConfig fields)
        foreach ($sortedPipes as $pipe) {
            $pipeResolved = $pipe->resolve($tenant, $configArray);
            $resolvedConfig = array_merge($resolvedConfig, $pipeResolved);
        }

        // Add tenant identity properties for frontend
        // For child tenants, use parent's identity since they represent the same logical tenant
        $identityTenant = $tenant->parent ?? $tenant;
        $resolvedConfig['tenantId'] = $identityTenant->public_id;
        $resolvedConfig['tenantName'] = $identityTenant->name;

        return $resolvedConfig;
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
