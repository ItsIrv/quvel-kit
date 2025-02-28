<?php

namespace Modules\Tenant\app\Services;

use Modules\Tenant\app\Models\Tenant;

class TenantFindService
{
    /**
     * Finds the parent tenant entity of the domain.
     *
     * @return Tenant|null
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
}
