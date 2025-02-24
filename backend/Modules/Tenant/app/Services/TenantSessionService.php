<?php

namespace Modules\Tenant\app\Services;

use Illuminate\Contracts\Session\Session;
use Modules\Tenant\app\Models\Tenant;

/**
 * Service to manage the tenant session.
 */
class TenantSessionService
{
    private const string TENANT_KEY = 'tenant';

    public function __construct(protected Session $store)
    {
    }

    /**
     * Check if a tenant is stored in the session.
     */
    public function hasTenant(): bool
    {
        return $this->store->has(self::TENANT_KEY);
    }

    /**
     * Retrieve the tenant from the session.
     */
    public function getTenant(): ?Tenant
    {
        /** @var array<string, string> $attributes */
        $attributes = $this->store->get(self::TENANT_KEY);

        if (empty($attributes)) {
            return null;
        }

        $tenant = new Tenant();

        foreach ($attributes as $key => $value) {
            $tenant->setAttribute($key, $value);
        }

        return $tenant;
    }

    /**
     * Store a tenant in the session.
     */
    public function setTenant(Tenant $tenant): void
    {
        $this->store->put(
            self::TENANT_KEY,
            $tenant->only(['public_id', 'name', 'domain']),
        );
    }
}
