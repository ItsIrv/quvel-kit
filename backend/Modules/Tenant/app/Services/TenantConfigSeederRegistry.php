<?php

namespace Modules\Tenant\Services;

class TenantConfigSeederRegistry
{
    /**
     * Registered config seeders by tier.
     */
    protected array $seeders = [
        'basic'      => [],
        'standard'   => [],
        'premium'    => [],
        'enterprise' => [],
    ];

    /**
     * Registered visibility seeders by tier.
     */
    protected array $visibilitySeeders = [
        'basic'      => [],
        'standard'   => [],
        'premium'    => [],
        'enterprise' => [],
    ];

    /**
     * Register a config seeder for a specific tier.
     *
     * @param string $tier The tier to register for
     * @param callable $seeder A callable that returns config array
     * @param int $priority Lower numbers run first
     * @param callable|null $visibilitySeeder Optional callable that returns visibility array
     * @return void
     */
    public function registerSeeder(string $tier, callable $seeder, int $priority = 50, ?callable $visibilitySeeder = null): void
    {
        if (!isset($this->seeders[$tier])) {
            $this->seeders[$tier] = [];
        }

        $this->seeders[$tier][] = [
            'seeder'   => $seeder,
            'priority' => $priority,
        ];

        // Sort by priority
        usort($this->seeders[$tier], fn ($a, $b) => $a['priority'] <=> $b['priority']);

        // Register visibility seeder if provided
        if ($visibilitySeeder !== null) {
            if (!isset($this->visibilitySeeders[$tier])) {
                $this->visibilitySeeders[$tier] = [];
            }

            $this->visibilitySeeders[$tier][] = [
                'seeder'   => $visibilitySeeder,
                'priority' => $priority,
            ];

            // Sort by priority
            usort($this->visibilitySeeders[$tier], fn ($a, $b) => $a['priority'] <=> $b['priority']);
        }
    }

    /**
     * Register a config seeder for all tiers.
     *
     * @param callable $seeder A callable that returns config array
     * @param int $priority Lower numbers run first
     * @param callable|null $visibilitySeeder Optional callable that returns visibility array
     * @return void
     */
    public function registerSeederForAllTiers(callable $seeder, int $priority = 50, ?callable $visibilitySeeder = null): void
    {
        foreach (array_keys($this->seeders) as $tier) {
            $this->registerSeeder($tier, $seeder, $priority, $visibilitySeeder);
        }
    }

    /**
     * Register config seeders for multiple tiers.
     *
     * @param array $tiers Array of tiers
     * @param callable $seeder A callable that returns config array
     * @param int $priority Lower numbers run first
     * @param callable|null $visibilitySeeder Optional callable that returns visibility array
     * @return void
     */
    public function registerSeederForTiers(array $tiers, callable $seeder, int $priority = 50, ?callable $visibilitySeeder = null): void
    {
        foreach ($tiers as $tier) {
            $this->registerSeeder($tier, $seeder, $priority, $visibilitySeeder);
        }
    }

    /**
     * Get seeded config for a specific tier.
     *
     * @param string $tier
     * @param array $baseConfig Base configuration to merge with
     * @return array
     */
    public function getSeedConfig(string $tier, array $baseConfig = []): array
    {
        $config = $baseConfig;

        if (!isset($this->seeders[$tier])) {
            return $config;
        }

        foreach ($this->seeders[$tier] as $seederData) {
            $seederConfig = call_user_func($seederData['seeder'], $tier, $config);
            if (is_array($seederConfig)) {
                $config = array_merge($config, $seederConfig);
            }
        }

        return $config;
    }

    /**
     * Get visibility config for a specific tier.
     * This is separate from seed config to maintain clean separation.
     *
     * @param string $tier
     * @param array $baseVisibility Base visibility to merge with
     * @return array
     */
    public function getSeedVisibility(string $tier, array $baseVisibility = []): array
    {
        $visibility = $baseVisibility;

        if (!isset($this->visibilitySeeders[$tier])) {
            return $visibility;
        }

        foreach ($this->visibilitySeeders[$tier] as $seederData) {
            $seederVisibility = call_user_func($seederData['seeder'], $tier, $visibility);
            if (is_array($seederVisibility)) {
                $visibility = array_merge($visibility, $seederVisibility);
            }
        }

        return $visibility;
    }
}
