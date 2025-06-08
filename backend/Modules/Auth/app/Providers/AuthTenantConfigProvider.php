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
        $tenantConfig = $tenant->config;

        // Extract auth-related configuration from tenant's config
        $authConfig = [];

        if ($tenantConfig) {
            // Get values from tenant's dynamic config
            $authConfig['socialiteProviders'] = $tenantConfig->get('socialite_providers', ['google']);
            $authConfig['passwordMinLength']  = $tenantConfig->get('password_min_length', 8);
            $authConfig['sessionCookie']      = $tenantConfig->get('session_cookie', 'quvel_session');
            $authConfig['twoFactorEnabled']   = $tenantConfig->get('two_factor_enabled', false);

            // Add session lifetime if present
            if ($tenantConfig->has('session_lifetime')) {
                $authConfig['sessionLifetime'] = $tenantConfig->get('session_lifetime');
            }
        } else {
            // Fallback defaults
            $authConfig = [
            ];
        }

        return [
            'config'     => $authConfig,
            'visibility' => [
                'socialiteProviders' => 'public',
                'passwordMinLength'  => 'public',
                'sessionCookie'      => 'protected',
                'twoFactorEnabled'   => 'public',
                'sessionLifetime'    => 'protected',
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
