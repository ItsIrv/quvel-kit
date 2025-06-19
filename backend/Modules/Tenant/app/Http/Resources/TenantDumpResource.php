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
 * Tenant resource.
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
class TenantDumpResource extends JsonResource
{
    private bool $isPublicAccess = false;

    /**
     * Set the access level for the resource.
     */
    public function setPublicAccess(bool $isPublic = true): self
    {
        $this->isPublicAccess = $isPublic;
        return $this;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->public_id,
            'name'      => $this->name,
            'domain'    => $this->domain,
            'parent_id' => $this->parent->public_id ?? null,
            'config'    => $this->getFilteredConfig(),
        ];
    }
    /**
     * Get filtered config using pipeline resolution.
     * This method applies configuration pipes to resolve values including calculated defaults.
     * @return array<string, mixed>
     */
    private function getFilteredConfig(): array
    {
        // Get the merged config array from getEffectiveConfig for pipeline resolution
        $tenantConfig = $this->resource->getEffectiveConfig();

        // Handle null config case - use empty array but still run pipeline
        $configArray = $tenantConfig?->toArray()['config'] ?? [];

        // Resolve using the merged configuration array
        $pipeline = app(ConfigurationPipeline::class);
        $resolved = $pipeline->resolveFromArray($this->resource, $configArray);

        // Build enhanced config with values and visibility from pipes
        $enhancedConfig = new DynamicTenantConfig();

        // Set values from pipeline resolution
        foreach ($resolved['values'] ?? [] as $key => $value) {
            $enhancedConfig->set($key, $value);
        }

        // Set visibility from pipeline resolution
        foreach ($resolved['visibility'] ?? [] as $key => $visibility) {
            $enhancedConfig->setVisibility($key, TenantConfigVisibility::from($visibility));
        }

        // Get the config based on access level
        $configData = $this->isPublicAccess
            ? $enhancedConfig->getPublicConfig()
            : $enhancedConfig->getProtectedConfig();

        // Build visibility array
        $visibility = [];
        foreach ($configData as $key => $value) {
            $visibility[$key] = $enhancedConfig->getVisibility($key)->value;
        }

        // Add __visibility key for frontend compatibility
        $configData['__visibility'] = $visibility;

        return $configData;
    }
}
