<?php

namespace Modules\Tenant\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Modules\Tenant\Enums\TenantConfigVisibility;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\ValueObjects\TenantConfig;

/**
 * Tenant resource.
 *
 * @property string $id
 * @property string $public_id
 * @property string $name
 * @property string $domain
 * @property Tenant|null $parent
 * @property TenantConfig|null $config
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class TenantDumpTransformer extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'name' => $this->name,
            'domain' => $this->domain,
            'parent_id' => $this->parent->public_id ?? null,
            'config' => $this->getFilteredConfig(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Get filtered tenant config based on visibility rules.
     *
     * @return array<string, mixed>
     */
    private function getFilteredConfig(): array
    {
        if (! $this->config instanceof TenantConfig) {
            return [];
        }

        $configArray = $this->config->toArray();
        $visibility = $configArray['__visibility'] ?? [];

        // Filter config based on visibility rules
        $filteredConfig = array_filter(
            $configArray,
            function ($key) use ($visibility): bool {
                return isset($visibility[$key]) && $visibility[$key] !== TenantConfigVisibility::PRIVATE->value;
            },
            ARRAY_FILTER_USE_KEY,
        );

        // Manually include __visibility
        $filteredConfig['__visibility'] = $visibility;

        return $filteredConfig;
    }
}
