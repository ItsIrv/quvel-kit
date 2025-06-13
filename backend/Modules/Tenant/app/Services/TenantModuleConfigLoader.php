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
                $seederConfig = $moduleConfig['seeders'][$template];
                
                // Handle new class-based seeders
                if (is_string($seederConfig)) {
                    $seeders[] = [
                        'seeder_class' => $seederConfig,
                        'priority' => $this->getSeederPriority($seederConfig),
                    ];
                } else {
                    // Legacy array configuration
                    $seeders[] = $seederConfig;
                }
            }

            // Add shared seeders that apply to all templates
            if (isset($moduleConfig['shared_seeders']) && is_array($moduleConfig['shared_seeders'])) {
                foreach ($moduleConfig['shared_seeders'] as $sharedSeederKey => $sharedSeeder) {
                    // Handle new class-based shared seeders
                    if (is_string($sharedSeeder)) {
                        $seeders[] = [
                            'shared_seeder_class' => $sharedSeeder,
                            'priority' => $this->getSharedSeederPriority($sharedSeeder),
                        ];
                    } else {
                        // Legacy array configuration
                        $seeders[] = $sharedSeeder;
                    }
                }
            }
        }

        // Sort by priority (lower numbers first)
        usort($seeders, function ($a, $b) {
            $aPriority = $a['priority'] ?? $this->getSeederClassPriority($a) ?? 50;
            $bPriority = $b['priority'] ?? $this->getSeederClassPriority($b) ?? 50;
            return $aPriority <=> $bPriority;
        });

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
                foreach ($moduleConfig['tables'] as $tableName => $tableConfig) {
                    // Handle class references
                    if (is_string($tableConfig) && class_exists($tableConfig)) {
                        $instance = app($tableConfig);
                        if ($instance instanceof \Modules\Tenant\Contracts\TenantTableConfigInterface) {
                            $tables[$tableName] = $instance->getConfig()->toArray();
                        }
                    }
                    // Handle value objects
                    elseif ($tableConfig instanceof \Modules\Tenant\ValueObjects\TenantTableConfig) {
                        $tables[$tableName] = $tableConfig->toArray();
                    }
                    // Handle legacy arrays
                    elseif (is_array($tableConfig)) {
                        $tables[$tableName] = $tableConfig;
                    }
                }
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
            if (isset($moduleConfig['exclusions'])) {
                $exclusions = $moduleConfig['exclusions'];
                
                // Handle class references
                if (is_string($exclusions) && class_exists($exclusions)) {
                    $instance = app($exclusions);
                    if ($instance instanceof \Modules\Tenant\Contracts\TenantExclusionConfigInterface) {
                        $exclusions = $instance->getConfig()->toArray();
                    }
                }
                // Handle value objects
                elseif ($exclusions instanceof \Modules\Tenant\ValueObjects\TenantExclusionConfig) {
                    $exclusions = $exclusions->toArray();
                }
                
                // Extract paths
                $exclusionPaths = \Illuminate\Support\Arr::get($exclusions, 'paths', []);
                if (is_array($exclusionPaths)) {
                    $paths = array_merge($paths, $exclusionPaths);
                }
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
            if (isset($moduleConfig['exclusions'])) {
                $exclusions = $moduleConfig['exclusions'];
                
                // Handle class references
                if (is_string($exclusions) && class_exists($exclusions)) {
                    $instance = app($exclusions);
                    if ($instance instanceof \Modules\Tenant\Contracts\TenantExclusionConfigInterface) {
                        $exclusions = $instance->getConfig()->toArray();
                    }
                }
                // Handle value objects
                elseif ($exclusions instanceof \Modules\Tenant\ValueObjects\TenantExclusionConfig) {
                    $exclusions = $exclusions->toArray();
                }
                
                // Extract patterns
                $exclusionPatterns = \Illuminate\Support\Arr::get($exclusions, 'patterns', []);
                if (is_array($exclusionPatterns)) {
                    $patterns = array_merge($patterns, $exclusionPatterns);
                }
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

    /**
     * Get priority for a template-specific seeder class.
     *
     * @param string $seederClass
     * @return int
     */
    private function getSeederPriority(string $seederClass): int
    {
        try {
            $instance = app($seederClass);
            return $instance->getPriority();
        } catch (\Exception) {
            return 50; // Default priority
        }
    }

    /**
     * Get priority for a shared seeder class.
     *
     * @param string $seederClass
     * @return int
     */
    private function getSharedSeederPriority(string $seederClass): int
    {
        try {
            $instance = app($seederClass);
            return $instance->getPriority();
        } catch (\Exception) {
            return 50; // Default priority
        }
    }

    /**
     * Get priority from a seeder data array that might contain class info.
     *
     * @param array $seederData
     * @return int|null
     */
    private function getSeederClassPriority(array $seederData): ?int
    {
        if (isset($seederData['seeder_class'])) {
            return $this->getSeederPriority($seederData['seeder_class']);
        }
        
        if (isset($seederData['shared_seeder_class'])) {
            return $this->getSharedSeederPriority($seederData['shared_seeder_class']);
        }
        
        return null;
    }
}
