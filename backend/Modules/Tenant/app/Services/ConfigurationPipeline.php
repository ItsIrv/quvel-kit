<?php

namespace Modules\Tenant\Services;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Collection;
use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Contracts\ConfigurationPipeInterface;
use Modules\Tenant\Models\Tenant;

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
        $tenantContext = app(TenantContext::class);
        if ($tenantContext->isBypassed()) {
            return;
        }

        $tenantConfig = $tenant->getEffectiveConfig();

        if ($tenantConfig === null) {
            return;
        }

        // Convert to array for pipeline processing
        $configArray = $tenantConfig->toArray()['config'];

        // Sort pipes by priority (higher priority first, defaults to array index)
        $sortedPipes = $this->pipes
            ->map(fn (ConfigurationPipeInterface $pipe, int $index) => [
                'pipe'     => $pipe,
                'priority' => method_exists($pipe, 'priority') ? $pipe->priority() : (1000 - $index)
            ])
            ->sortByDesc('priority')
            ->map(fn (array $item) => function ($passable, $next) use ($item) {
                return $item['pipe']->handle(
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
     * Resolve configuration values from a configuration array without side effects.
     * This method works on merged configuration arrays and is the core resolution logic.
     * Returns both values and visibility for frontend consumption.
     *
     * @param Tenant $tenant The tenant context for resolution
     * @param array<string, mixed> $configArray The merged configuration array to resolve
     * @return array<string, mixed> ['values' => array, 'visibility' => array]
     */
    public function resolveFromArray(Tenant $tenant, array $configArray): array
    {
        $allValues     = [];
        $allVisibility = [];

        // Sort pipes by priority (higher priority first, defaults to array index)
        $sortedPipes = $this->pipes
            ->map(fn (ConfigurationPipeInterface $pipe, int $index) => [
                'pipe'     => $pipe,
                'priority' => method_exists($pipe, 'priority') ? $pipe->priority() : (1000 - $index)
            ])
            ->sortByDesc('priority')
            ->pluck('pipe');

        // Apply each pipe's resolution
        foreach ($sortedPipes as $pipe) {
            $pipeResult = $pipe->resolve($tenant, $configArray);

            if (isset($pipeResult['values'])) {
                $allValues = array_merge($allValues, $pipeResult['values']);
            }

            if (isset($pipeResult['visibility'])) {
                $allVisibility = array_merge($allVisibility, $pipeResult['visibility']);
            }
        }

        // Add tenant identity properties for frontend
        $identityTenant              = $tenant->parent ?? $tenant;
        $allValues['tenantId']       = $identityTenant->public_id;
        $allValues['tenantName']     = $identityTenant->name;
        $allVisibility['tenantId']   = 'public';
        $allVisibility['tenantName'] = 'public';

        return ['values' => $allValues, 'visibility' => $allVisibility];
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

}
