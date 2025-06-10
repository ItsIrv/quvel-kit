<?php

namespace Modules\Tenant\Services;

use Nwidart\Modules\Facades\Module;

/**
 * Service for loading tenant configuration from all modules.
 */
class TenantModuleConfigLoader
{
    private ?array $loadedConfigs = null;

    /**
     * Load tenant configuration from all modules.
     *
     * @return array Module name => config array
     */
    public function loadAllModuleConfigs(): array
    {
        if ($this->loadedConfigs !== null) {
            return $this->loadedConfigs;
        }

        $configs = [];
        $modules = Module::toCollection();

        foreach ($modules as $module) {
            $configPath = $module->getPath() . '/config/tenant.php';

            if (file_exists($configPath)) {
                $configs[$module->getName()] = require $configPath;
            }
        }

        return $this->loadedConfigs = $configs;
    }

    /**
     * Get all seeder configurations for a specific template.
     *
     * @param string $template
     * @return array
     */
    public function getSeedersForTemplate(string $template): array
    {
        $seeders = [];

        foreach ($this->loadAllModuleConfigs() as $moduleConfig) {
            // Add template-specific seeders
            if (isset($moduleConfig['seeders'][$template])) {
                $seeders[] = $moduleConfig['seeders'][$template];
            }

            // Add shared seeders that apply to all templates
            if (isset($moduleConfig['shared_seeders']) && is_array($moduleConfig['shared_seeders'])) {
                foreach ($moduleConfig['shared_seeders'] as $sharedSeeder) {
                    $seeders[] = $sharedSeeder;
                }
            }
        }

        // Sort by priority (lower numbers first)
        usort($seeders, fn ($a, $b) => ($a['priority'] ?? 50) <=> ($b['priority'] ?? 50));

        return $seeders;
    }

    /**
     * Get all configuration pipes from modules.
     *
     * @return array
     */
    public function getAllPipes(): array
    {
        $pipes = [];

        foreach ($this->loadAllModuleConfigs() as $moduleConfig) {
            if (isset($moduleConfig['pipes']) && is_array($moduleConfig['pipes'])) {
                $pipes = array_merge($pipes, $moduleConfig['pipes']);
            }
        }

        return $pipes;
    }

    /**
     * Get all tenant tables from modules.
     *
     * @return array
     */
    public function getAllTables(): array
    {
        $tables = [];

        foreach ($this->loadAllModuleConfigs() as $moduleConfig) {
            if (isset($moduleConfig['tables']) && is_array($moduleConfig['tables'])) {
                $tables = array_merge($tables, $moduleConfig['tables']);
            }
        }

        return $tables;
    }

    /**
     * Get all exclusion paths from modules.
     *
     * @return array
     */
    public function getAllExclusionPaths(): array
    {
        $paths = [];

        foreach ($this->loadAllModuleConfigs() as $moduleConfig) {
            if (isset($moduleConfig['exclusions']['paths']) && is_array($moduleConfig['exclusions']['paths'])) {
                $paths = array_merge($paths, $moduleConfig['exclusions']['paths']);
            }
        }

        return $paths;
    }

    /**
     * Get all exclusion patterns from modules.
     *
     * @return array
     */
    public function getAllExclusionPatterns(): array
    {
        $patterns = [];

        foreach ($this->loadAllModuleConfigs() as $moduleConfig) {
            if (isset($moduleConfig['exclusions']['patterns']) && is_array($moduleConfig['exclusions']['patterns'])) {
                $patterns = array_merge($patterns, $moduleConfig['exclusions']['patterns']);
            }
        }

        return $patterns;
    }

    /**
     * Get visibility configurations for a specific template.
     *
     * @param string $template
     * @return array
     */
    public function getVisibilityForTemplate(string $template): array
    {
        $visibility = [];

        foreach ($this->loadAllModuleConfigs() as $moduleConfig) {
            // Add template-specific visibility
            if (isset($moduleConfig['seeders'][$template]['visibility']) && is_array($moduleConfig['seeders'][$template]['visibility'])) {
                $visibility = array_merge($visibility, $moduleConfig['seeders'][$template]['visibility']);
            }

            // Add shared seeder visibility that applies to all templates
            if (isset($moduleConfig['shared_seeders']) && is_array($moduleConfig['shared_seeders'])) {
                foreach ($moduleConfig['shared_seeders'] as $sharedSeeder) {
                    if (isset($sharedSeeder['visibility']) && is_array($sharedSeeder['visibility'])) {
                        $visibility = array_merge($visibility, $sharedSeeder['visibility']);
                    }
                }
            }
        }

        return $visibility;
    }
}
