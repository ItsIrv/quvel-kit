<?php

namespace Modules\Tenant\Services;

class TenantConfigSeederRegistry
{
    /**
     * Registered config seeders by template.
     */
    protected array $seeders = [
        'basic'    => [],
        'standard' => [],
        'isolated' => [],
    ];

    /**
     * Registered visibility seeders by template.
     */
    protected array $visibilitySeeders = [
        'basic'    => [],
        'standard' => [],
        'isolated' => [],
    ];

    /**
     * Register a config seeder for a specific template.
     *
     * @param string $template The template to register for
     * @param callable $seeder A callable that returns config array
     * @param int $priority Lower numbers run first
     * @param callable|null $visibilitySeeder Optional callable that returns visibility array
     * @return void
     */
    public function registerSeeder(string $template, callable $seeder, int $priority = 50, ?callable $visibilitySeeder = null): void
    {
        if (!isset($this->seeders[$template])) {
            $this->seeders[$template] = [];
        }

        $this->seeders[$template][] = [
            'seeder'   => $seeder,
            'priority' => $priority,
        ];

        // Sort by priority
        usort($this->seeders[$template], fn ($a, $b) => $a['priority'] <=> $b['priority']);

        // Register visibility seeder if provided
        if ($visibilitySeeder !== null) {
            if (!isset($this->visibilitySeeders[$template])) {
                $this->visibilitySeeders[$template] = [];
            }

            $this->visibilitySeeders[$template][] = [
                'seeder'   => $visibilitySeeder,
                'priority' => $priority,
            ];

            // Sort by priority
            usort($this->visibilitySeeders[$template], fn ($a, $b) => $a['priority'] <=> $b['priority']);
        }
    }

    /**
     * Register a config seeder for all templates.
     *
     * @param callable $seeder A callable that returns config array
     * @param int $priority Lower numbers run first
     * @param callable|null $visibilitySeeder Optional callable that returns visibility array
     * @return void
     */
    public function registerSeederForAllTemplates(callable $seeder, int $priority = 50, ?callable $visibilitySeeder = null): void
    {
        foreach (array_keys($this->seeders) as $template) {
            $this->registerSeeder($template, $seeder, $priority, $visibilitySeeder);
        }
    }

    /**
     * Register config seeders for multiple templates.
     *
     * @param array $templates Array of templates
     * @param callable $seeder A callable that returns config array
     * @param int $priority Lower numbers run first
     * @param callable|null $visibilitySeeder Optional callable that returns visibility array
     * @return void
     */
    public function registerSeederForTemplates(array $templates, callable $seeder, int $priority = 50, ?callable $visibilitySeeder = null): void
    {
        foreach ($templates as $template) {
            $this->registerSeeder($template, $seeder, $priority, $visibilitySeeder);
        }
    }

    /**
     * Get seeded config for a specific template.
     *
     * @param string $template
     * @param array $baseConfig Base configuration to merge with
     * @return array
     */
    public function getSeedConfig(string $template, array $baseConfig = []): array
    {
        $config = $baseConfig;

        if (!isset($this->seeders[$template])) {
            return $config;
        }

        foreach ($this->seeders[$template] as $seederData) {
            $seederConfig = call_user_func($seederData['seeder'], $template, $config);
            if (is_array($seederConfig)) {
                $config = array_merge($config, $seederConfig);
            }
        }

        return $config;
    }

    /**
     * Get visibility config for a specific template.
     * This is separate from seed config to maintain clean separation.
     *
     * @param string $template
     * @param array $baseVisibility Base visibility to merge with
     * @return array
     */
    public function getSeedVisibility(string $template, array $baseVisibility = []): array
    {
        $visibility = $baseVisibility;

        if (!isset($this->visibilitySeeders[$template])) {
            return $visibility;
        }

        foreach ($this->visibilitySeeders[$template] as $seederData) {
            $seederVisibility = call_user_func($seederData['seeder'], $template, $visibility);
            if (is_array($seederVisibility)) {
                $visibility = array_merge($visibility, $seederVisibility);
            }
        }

        return $visibility;
    }
}
