<?php

namespace Modules\Tenant\Services;

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
            $seederConfig = is_callable($seederData['config'])
                ? call_user_func($seederData['config'], $template, $config)
                : $seederData['config'];

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
            if (is_array($visibilityData)) {
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
}
