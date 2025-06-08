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

        if ($tenantConfig === null) {
            return [
                'config'     => [],
                'visibility' => [],
            ];
        }

        $config     = [];
        $visibility = [];

        // Only add keys if they have values set
        if ($tenantConfig->has('socialite_providers')) {
            $config['socialiteProviders']     = $tenantConfig->get('socialite_providers');
            $visibility['socialiteProviders'] = 'public';
        }

        if ($tenantConfig->has('session_cookie')) {
            $config['sessionCookie']     = $tenantConfig->get('session_cookie');
            $visibility['sessionCookie'] = 'protected';
        }

        return [
            'config'     => $config,
            'visibility' => $visibility,
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
