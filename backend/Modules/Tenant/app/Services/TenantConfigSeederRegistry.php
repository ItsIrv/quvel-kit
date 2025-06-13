<?php

namespace Modules\Tenant\Services;

use Modules\Tenant\Contracts\TenantConfigSeederInterface;
use Modules\Tenant\Contracts\TenantSharedSeederInterface;

class TenantConfigSeederRegistry
{
    /**
     * Evaluated seeders cache by template.
     */
    private array $evaluatedSeeders = [];

    /**
     * Evaluated visibility cache by template.
     */
    private array $evaluatedVisibility = [];

    /**
     * Instantiated seeder classes cache.
     */
    private array $seederInstances = [];

    /**
     * Instantiated shared seeder classes cache.
     */
    private array $sharedSeederInstances = [];

    public function __construct(
        private TenantModuleConfigLoader $loader
    ) {
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
        if (!isset($this->evaluatedSeeders[$template])) {
            $this->loadSeedersForTemplate($template);
        }

        $config = $baseConfig;

        foreach ($this->evaluatedSeeders[$template] as $seederData) {
            // Handle new class-based seeders
            if (isset($seederData['seeder_class'])) {
                $seeder = $this->getSeederInstance($seederData['seeder_class']);
                $seederConfig = $seeder->getConfig($template, $config);
            } 
            // Handle shared seeders
            elseif (isset($seederData['shared_seeder_class'])) {
                $seeder = $this->getSharedSeederInstance($seederData['shared_seeder_class']);
                $seederConfig = $seeder->getSharedConfig($template, $config);
            }
            // Legacy closure support (backward compatibility)
            else {
                $seederConfig = is_callable($seederData['config'])
                    ? call_user_func($seederData['config'], $template, $config)
                    : $seederData['config'];
            }

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
        if (!isset($this->evaluatedVisibility[$template])) {
            $this->loadVisibilityForTemplate($template);
        }

        $visibility = $baseVisibility;

        foreach ($this->evaluatedVisibility[$template] as $visibilityData) {
            // Handle class-based visibility from seeders
            if (isset($visibilityData['seeder_class'])) {
                $seeder = $this->getSeederInstance($visibilityData['seeder_class']);
                $seederVisibility = $seeder->getVisibility();
                if (is_array($seederVisibility)) {
                    $visibility = array_merge($visibility, $seederVisibility);
                }
            }
            // Handle shared seeder visibility
            elseif (isset($visibilityData['shared_seeder_class'])) {
                $seeder = $this->getSharedSeederInstance($visibilityData['shared_seeder_class']);
                $seederVisibility = $seeder->getVisibility();
                if (is_array($seederVisibility)) {
                    $visibility = array_merge($visibility, $seederVisibility);
                }
            }
            // Legacy array visibility
            elseif (is_array($visibilityData)) {
                $visibility = array_merge($visibility, $visibilityData);
            }
        }

        return $visibility;
    }

    /**
     * Load and cache seeders for a specific template.
     *
     * @param string $template
     * @return void
     */
    private function loadSeedersForTemplate(string $template): void
    {
        $this->evaluatedSeeders[$template] = $this->loader->getSeedersForTemplate($template);
    }

    /**
     * Load and cache visibility for a specific template.
     *
     * @param string $template
     * @return void
     */
    private function loadVisibilityForTemplate(string $template): void
    {
        $visibility = [];

        foreach ($this->loader->loadAllModuleConfigs() as $moduleConfig) {
            // Add template-specific visibility
            if (isset($moduleConfig['seeders'][$template]['visibility']) && is_array($moduleConfig['seeders'][$template]['visibility'])) {
                $visibility[] = $moduleConfig['seeders'][$template]['visibility'];
            }

            // Add shared seeder visibility that applies to all templates
            if (isset($moduleConfig['shared_seeders']) && is_array($moduleConfig['shared_seeders'])) {
                foreach ($moduleConfig['shared_seeders'] as $sharedSeeder) {
                    if (isset($sharedSeeder['visibility']) && is_array($sharedSeeder['visibility'])) {
                        $visibility[] = $sharedSeeder['visibility'];
                    }
                }
            }
        }

        $this->evaluatedVisibility[$template] = $visibility;
    }

    /**
     * Get a seeder instance, creating and caching it if necessary.
     *
     * @param string $seederClass
     * @return TenantConfigSeederInterface
     */
    private function getSeederInstance(string $seederClass): TenantConfigSeederInterface
    {
        if (!isset($this->seederInstances[$seederClass])) {
            $this->seederInstances[$seederClass] = app($seederClass);
        }

        return $this->seederInstances[$seederClass];
    }

    /**
     * Get a shared seeder instance, creating and caching it if necessary.
     *
     * @param string $seederClass
     * @return TenantSharedSeederInterface
     */
    private function getSharedSeederInstance(string $seederClass): TenantSharedSeederInterface
    {
        if (!isset($this->sharedSeederInstances[$seederClass])) {
            $this->sharedSeederInstances[$seederClass] = app($seederClass);
        }

        return $this->sharedSeederInstances[$seederClass];
    }
}
