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
        return [
            'config' => [
                // Auth-specific configurations
                'auth_providers' => config('auth.socialite.providers', []),
                'password_min_length' => config('auth.password_min_length', 8),
                'session_timeout' => config('auth.session_timeout', 120),
                'two_factor_enabled' => config('auth.two_factor_enabled', false),
            ],
            'visibility' => [
                'auth_providers' => 'public',
                'password_min_length' => 'public',
                'session_timeout' => 'public',
                'two_factor_enabled' => 'public',
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