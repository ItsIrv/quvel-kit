<?php

namespace Modules\Tenant\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Tenant resource.
 *
 * @property string $id
 * @property string $public_id
 * @property string $name
 * @property string $domain
 * @property \Modules\Tenant\Models\Tenant|null $parent
 * @property \Modules\Tenant\ValueObjects\TenantConfig|null $config
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class TenantDumpTransformer extends JsonResource
{
    /**
     * Transform the resource into an array.
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->public_id,
            'name'       => $this->name,
            'domain'     => $this->domain,
            'parent_id'  => $this->parent->public_id ?? null,
            'config'     => $this->config->toArray(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
