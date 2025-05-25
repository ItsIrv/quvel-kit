<?php

namespace Modules\Core\Providers;

use Modules\Tenant\Contracts\TenantConfigProviderInterface;
use Modules\Tenant\Models\Tenant;

/**
 * Provides Core module configuration for tenant responses.
 */
class CoreTenantConfigProvider implements TenantConfigProviderInterface
{
    /**
     * Get Core module configuration for the tenant.
     *
     * @param Tenant $tenant
     * @return array{config: array<string, mixed>, visibility: array<string, string>}
     */
    public function getConfig(Tenant $tenant): array
    {
        return [
            'config'     => [
                // Core configuration matching TypeScript interface
                'apiUrl'                 => config('app.url') . '/api',
                'appUrl'                 => config('frontend.url'),
                'appName'                => config('app.name', 'Quvel Kit'),
                'tenantId'               => $tenant->id,
                'tenantName'             => $tenant->name,
                'pusherAppKey'           => config('broadcasting.connections.pusher.key', ''),
                'pusherAppCluster'       => config('broadcasting.connections.pusher.options.cluster', 'eu'),
                'recaptchaGoogleSiteKey' => config('services.recaptcha.site_key', ''),

                // Additional Core module specific configs
                'internalApiUrl'         => config('frontend.internal_api_url'),
            ],
            'visibility' => [
                'apiUrl'                 => 'public',
                'appUrl'                 => 'public',
                'appName'                => 'public',
                'tenantId'               => 'public',
                'tenantName'             => 'public',
                'pusherAppKey'           => 'public',
                'pusherAppCluster'       => 'public',
                'recaptchaGoogleSiteKey' => 'public',
                'internalApiUrl'         => 'protected',
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
        return 100; // High priority as Core module
    }
}
