<?php

namespace Modules\Tenant\app\Services;

use Illuminate\Contracts\Session\Session;
use Modules\Tenant\app\Models\Tenant;

class TenantSessionService
{
    public function __construct(protected Session $store)
    {
    }

    /**
     * Check if a tenant is stored in the session.
     */
    public function hasTenant(): bool
    {
        return $this->store->has(['tenant_id', 'tenant_domain']);
    }

    /**
     * Retrieve the tenant from the session.
     */
    public function getTenant(): ?Tenant
    {
        if (!$this->hasTenant()) {
            return null;
        }

        return new Tenant([
            'id'     => $this->store->get('tenant_id'),
            'domain' => $this->store->get('tenant_domain'),
        ]);
    }

    /**
     * Store a tenant in the session.
     */
    public function setTenant(Tenant $tenant): void
    {
        $this->store->put('tenant_id', $tenant->id);
        $this->store->put('tenant_domain', $tenant->domain);
    }

    /**
     * Clear the tenant session.
     */
    public function clearTenant(): void
    {
        $this->store->forget(['tenant_id', 'tenant_domain']);
    }
}
