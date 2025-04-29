<?php

namespace Modules\Tenant\Services;

use Illuminate\Contracts\Session\Session;
use Modules\Tenant\Models\Tenant;

/**
 * Service to manage the tenant session.
 * TODO: Change to Repository
 */
class TenantSessionService
{
    private const string TENANT_KEY = 'tenant';

    /**
     * Tenant session service constructor.
     */
    public function __construct(private readonly Session $store)
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
            $tenant->only([
                'public_id',
                'name',
                'domain',
                'created_at',
                'updated_at',
            ]),
        );
    }

    public function forget(): void
    {
        $this->store->forget(self::TENANT_KEY);
    }
}
