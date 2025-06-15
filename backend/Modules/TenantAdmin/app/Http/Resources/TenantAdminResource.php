<?php

namespace Modules\TenantAdmin\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantAdminResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Returns raw tenant data for admin use, including sensitive config keys.
     * This is different from TenantDumpResource which normalizes and restricts data.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->resource->id,
            'public_id'  => $this->resource->public_id,
            'name'       => $this->resource->name,
            'domain'     => $this->resource->domain,
            'parent_id'  => $this->resource->parent_id,
            'tier'       => $this->resource->tier,
            'config'     => $this->resource->config ? $this->resource->config->toArray()['config'] : [],
            'visibility' => $this->resource->config ? $this->resource->config->toArray()['visibility'] : [],
            'is_active'  => $this->resource->is_active,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
            'deleted_at' => $this->resource->deleted_at,
        ];
    }
}
