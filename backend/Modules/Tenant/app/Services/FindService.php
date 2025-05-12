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
        /** @phpstan-ignore-next-line TODO */
        $tenant = Tenant::where('domain', '=', $domain)
            ->orWhereHas('parent', fn ($query) => $query->where('domain', '=', $domain))
            ->first();

        if ($tenant?->parent) {
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
     * Get tenant public ID from tenant ID
     */
    public function getTenantPublicIdFromId(int $tenantId): ?string
    {
        return Tenant::select('public_id')->find($tenantId)?->public_id;
    }
}
