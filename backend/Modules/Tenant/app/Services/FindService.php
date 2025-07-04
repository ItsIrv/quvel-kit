<?php

namespace Modules\Tenant\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Tenant\Models\Tenant;

class FindService
{
    /**
     * Finds the parent tenant entity of the domain.
     */
    public function findTenantByDomain(string $domain): ?Tenant
    {
        $tenant = Tenant::where('domain', '=', $domain)
            ->orWhereHas('parent', fn ($query) => $query->where('domain', '=', $domain))
            ->first();

        if ($tenant !== null && $tenant->parent !== null) {
            return $tenant->parent; // Always return the parent
        }

        return $tenant;
    }

    /**
     * Summary of findAll
     *
     * @return Collection<int, Tenant>
     */
    public function findAll(): Collection
    {
        return Tenant::all();
    }

    /**
     * Find tenant by ID
     */
    public function findById(int $tenantId): ?Tenant
    {
        return Tenant::find($tenantId);
    }

    /**
     * Find tenant by public ID
     */
    public function findByPublicId(string $publicId): ?Tenant
    {
        return Tenant::where('public_id', '=', $publicId)->first();
    }

    /**
     * Get tenant public ID from tenant ID
     */
    public function getTenantPublicIdFromId(int $tenantId): ?string
    {
        return $this->findById($tenantId)?->public_id;
    }
}
