<?php

namespace Modules\Tenant\Services;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Modules\Tenant\Models\Tenant;

class ConfigApplier
{
    /**
     * Apply tenant-specific config at runtime using the configuration pipeline.
     */
    public static function apply(Tenant $tenant, ConfigRepository $appConfig): void
    {
        app(ConfigurationPipeline::class)->apply($tenant, $appConfig);
    }
}
