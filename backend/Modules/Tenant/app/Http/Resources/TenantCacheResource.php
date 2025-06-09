<?php

namespace Modules\Tenant\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\ValueObjects\DynamicTenantConfig;
use Modules\Tenant\Services\ConfigurationPipeline;
use Modules\Tenant\Enums\TenantConfigVisibility;

/**
 * Tenant cache resource that reads directly from tenant->config.
 * Used for the /tenant/cache endpoint to avoid reading from global config.
 *
 * @property string $id
 * @property string $public_id
 * @property string $name
 * @property string $domain
 * @property Tenant|null $parent
 * @property DynamicTenantConfig|null $config
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class TenantCacheResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->public_id,
            'name'       => $this->name,
            'domain'     => $this->domain,
            'parent_id'  => $this->parent->public_id ?? null,
            'config'     => $this->getFilteredConfig(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Get filtered config using pipeline resolution.
     * This method applies configuration pipes to resolve values including calculated defaults.
     */
    private function getFilteredConfig(): array
    {
        // Get base tenant config
        $baseConfig = $this->getEffectiveConfig()?->getProtectedConfig() ?? [];

        // Get pipeline-resolved config (including defaults like session cookie)
        $pipeline = app(ConfigurationPipeline::class);
        $resolvedConfig = $pipeline->resolve($this->resource);

        // Merge base + resolved (resolved takes precedence for defaults)
        $finalConfig = array_merge($baseConfig, $resolvedConfig);

        // Add tenant identity properties (not stored in config)
        $finalConfig['tenantId'] = $this->public_id;
        $finalConfig['tenantName'] = $this->name;

        // Build enhanced config with visibility
        $enhancedConfig = new DynamicTenantConfig();
        
        // Add all config values with appropriate visibility
        foreach ($finalConfig as $key => $value) {
            $enhancedConfig->set($key, $value);
            
            // Set visibility based on key patterns
            if (in_array($key, ['tenantId', 'tenantName', 'app_url', 'frontend_url', 'app_name', 'socialite_providers', 'pusher_app_key', 'pusher_app_cluster', 'recaptcha_site_key'])) {
                $enhancedConfig->setVisibility($key, TenantConfigVisibility::PUBLIC);
            } else {
                $enhancedConfig->setVisibility($key, TenantConfigVisibility::PROTECTED);
            }
        }

        // Get protected config (public + protected visibility)
        $protectedConfig = $enhancedConfig->getProtectedConfig();

        // Build visibility array
        $visibility = [];
        foreach ($protectedConfig as $key => $value) {
            $visibility[$key] = $enhancedConfig->getVisibility($key)->value;
        }

        // Add __visibility key for frontend compatibility
        $protectedConfig['__visibility'] = $visibility;

        return $protectedConfig;
    }
}
