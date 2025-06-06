<?php

namespace Modules\TenantAdmin\App\Http\Resources;

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
            'id'         => $this->id,
            'public_id'  => $this->public_id,
            'name'       => $this->name,
            'domain'     => $this->domain,
            'parent_id'  => $this->parent_id,
            'tier'       => $this->tier,
            'config'     => $this->config ? $this->config->toArray()['config'] : null,
            'visibility' => $this->config ? $this->config->toArray()['visibility'] : null,
            'is_active'  => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
