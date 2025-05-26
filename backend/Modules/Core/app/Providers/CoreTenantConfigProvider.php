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
        // Get tenant's effective config which includes seeded values
        $tenantConfig = $tenant->getEffectiveConfig();

        return [
            'config'     => [
                // Core configuration - read from tenant config when available
                'apiUrl'                 => $tenantConfig?->get('app_url', config('app.url')) ?? config('app.url'),
                'appUrl'                 => $tenantConfig?->get('frontend_url', config('frontend.url')) ?? config('frontend.url'),
                'appName'                => $tenantConfig?->get('app_name', config('app.name', 'Quvel Kit')) ?? config('app.name', 'Quvel Kit'),
                'tenantId'               => $tenant->public_id,
                'tenantName'             => $tenant->name,

                // Pusher config from tenant config
                'pusherAppKey'           => $tenantConfig?->get('pusher_app_key', '') ?? '',
                'pusherAppCluster'       => $tenantConfig?->get('pusher_app_cluster', 'mt1') ?? 'mt1',

                // reCAPTCHA config from tenant config (only site key is public)
                'recaptchaGoogleSiteKey' => $tenantConfig?->get('recaptcha_site_key', '') ?? '',

                // Additional Core module specific configs
                'internalApiUrl'         => $tenantConfig?->get('internal_api_url', config('frontend.internal_api_url')) ?? config('frontend.internal_api_url'),
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
