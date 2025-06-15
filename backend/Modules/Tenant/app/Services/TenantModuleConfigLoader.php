<?php

namespace Modules\Tenant\Services;

use Nwidart\Modules\Facades\Module;

/**
 * Service for loading tenant configuration from all modules.
 */
class TenantModuleConfigLoader
{
    /** @var array<string, mixed>|null */
    private ?array $loadedConfigs = null;

    /**
     * Load tenant configuration from all modules.
     *
     * @return array<string, mixed> Module name => config array
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
     * @return list<mixed>
     */
    public function getSeedersForTemplate(string $template): array
    {
        $seeders      = [];
        $currentIndex = 0;

        foreach ($this->loadAllModuleConfigs() as $moduleConfig) {
            // Add template-specific seeders
            if (isset($moduleConfig['seeders'][$template])) {
                $seederConfig = $moduleConfig['seeders'][$template];

                // Handle new class-based seeders
                if (is_string($seederConfig)) {
                    $seeders[] = [
                        'seeder_class' => $seederConfig,
                        'array_index'  => $currentIndex++,
                    ];
                } else {
                    // Legacy array configuration
                    $seederConfig['array_index'] = $currentIndex++;
                    $seeders[]                   = $seederConfig;
                }
            }

            // Add shared seeders that apply to all templates
            if (isset($moduleConfig['shared_seeders']) && is_array($moduleConfig['shared_seeders'])) {
                foreach ($moduleConfig['shared_seeders'] as $sharedSeederKey => $sharedSeeder) {
                    // Handle new class-based shared seeders
                    if (is_string($sharedSeeder)) {
                        $seeders[] = [
                            'shared_seeder_class' => $sharedSeeder,
                            'array_index'         => $currentIndex++,
                        ];
                    } else {
                        // Legacy array configuration
                        $sharedSeeder['array_index'] = $currentIndex++;
                        $seeders[]                   = $sharedSeeder;
                    }
                }
            }
        }

        // Sort by priority (lower numbers first, defaults to array index)
        usort($seeders, function ($a, $b) {
            $aPriority = $this->getSeederPriorityUnified($a);
            $bPriority = $this->getSeederPriorityUnified($b);
            return $aPriority <=> $bPriority; // Lower priority first
        });

        return $seeders;
    }

    /**
     * Get all configuration pipes from modules.
     *
     * @return array<string, mixed>
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
     * @return array<string, mixed>
     */
    public function getAllTables(): array
    {
        $tables = [];

        foreach ($this->loadAllModuleConfigs() as $moduleConfig) {
            if (isset($moduleConfig['tables']) && is_array($moduleConfig['tables'])) {
                // Handle simple array of table names
                foreach ($moduleConfig['tables'] as $key => $value) {
                    if (is_numeric($key) && is_string($value)) {
                        // Simple table name
                        $tables[] = $value;
                    } else {
                        // Associative array with table configurations
                        // Handle class references
                        if (is_string($value) && class_exists($value)) {
                            $instance = app($value);
                            if ($instance instanceof \Modules\Tenant\Contracts\TenantTableConfigInterface) {
                                $tables[$key] = $instance->getConfig()->toArray();
                            }
                        }
                        // Handle value objects
                        elseif ($value instanceof \Modules\Tenant\ValueObjects\TenantTableConfig) {
                            $tables[$key] = $value->toArray();
                        }
                        // Handle legacy arrays
                        elseif (is_array($value)) {
                            $tables[$key] = $value;
                        }
                    }
                }
            }
        }

        return $tables;
    }

    /**
     * Get all exclusion paths from modules.
     *
     * @return array<int, string>
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
     * @return array<int, string>
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
     * @return array<string, mixed>
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
     * Get unified priority for any seeder configuration.
     * Uses array index as default (converted to higher-first system).
     *
     * @param array<string, mixed> $seederData
     * @return int
     */
    private function getSeederPriorityUnified(array $seederData): int
    {
        // Check for explicit priority in array config
        if (isset($seederData['priority'])) {
            return $seederData['priority'];
        }

        // Check for priority method in seeder class
        if (isset($seederData['seeder_class'])) {
            $classPriority = $this->getSeederClassPriority($seederData['seeder_class']);
            if ($classPriority !== 500) { // 500 is our "no explicit priority" marker
                return $classPriority;
            }
        }

        if (isset($seederData['shared_seeder_class'])) {
            $classPriority = $this->getSharedSeederClassPriority($seederData['shared_seeder_class']);
            if ($classPriority !== 500) { // 500 is our "no explicit priority" marker
                return $classPriority;
            }
        }

        // Use array index as priority (convert to higher-first system)
        // Array index 0 gets highest priority (1000), index 1 gets 999, etc.
        if (isset($seederData['array_index'])) {
            return 1000 - $seederData['array_index'];
        }

        // Final fallback
        return 500;
    }

    /**
     * Get priority for a seeder class (template-specific or shared).
     *
     * @param string $seederClass
     * @return int
     */
    private function getSeederClassPriority(string $seederClass): int
    {
        try {
            $instance = app($seederClass);
            return method_exists($instance, 'getPriority') && is_object($instance) ? $instance->getPriority() : 500;
        } catch (\Exception) {
            return 500; // Default priority
        }
    }

    /**
     * Get priority for a shared seeder class.
     *
     * @param string $seederClass
     * @return int
     */
    private function getSharedSeederClassPriority(string $seederClass): int
    {
        try {
            $instance = app($seederClass);
            return method_exists($instance, 'getPriority') && is_object($instance) ? $instance->getPriority() : 500;
        } catch (\Exception) {
            return 500; // Default priority
        }
    }
}
