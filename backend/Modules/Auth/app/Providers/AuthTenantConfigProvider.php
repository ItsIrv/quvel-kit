<?php

namespace Modules\Auth\Providers;

use Modules\Tenant\Contracts\TenantConfigProviderInterface;
use Modules\Tenant\Models\Tenant;

/**
 * Provides Auth module configuration for tenant responses.
 */
class AuthTenantConfigProvider implements TenantConfigProviderInterface
{
    /**
     * Get Auth module configuration for the tenant.
     *
     * @param Tenant $tenant
     * @return array{config: array<string, mixed>, visibility: array<string, string>}
     */
    public function getConfig(Tenant $tenant): array
    {
        // Get tenant's dynamic config
        $tenantConfig = $tenant->getEffectiveConfig();

        return [
            'config'     => [
                'socialiteProviders' => $tenantConfig?->get('socialite_providers', ['google']),
                'sessionCookie'      => $tenantConfig?->get('session_cookie', 'quvel_session'),
            ],
            'visibility' => [
                'socialiteProviders' => 'public',
                'sessionCookie'      => 'protected',
            ],
        ];
    }

    /**
     * Get the priority for this provider.
     *
     * @return int
     */
    public function priority(): int
    {
        return 50; // Lower priority than Core
    }
}
