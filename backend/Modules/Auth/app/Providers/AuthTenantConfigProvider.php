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
            'config'     => [
                // Auth-specific configurations
                'socialiteProviders' => config('auth.socialite.providers', []),
                'passwordMinLength'  => config('auth.password_min_length', 8),
                'sessionCookie'      => config('session.cookie', 120),
                'twoFactorEnabled'   => config('auth.two_factor_enabled', false),
            ],
            'visibility' => [
                'socialiteProviders' => 'public',
                'passwordMinLength'  => 'public',
                'sessionCookie'      => 'public',
                'twoFactorEnabled'   => 'public',
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
