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

        $config = [];
        $visibility = [];

        // Always include tenant identity
        $config['tenantId'] = $tenant->public_id;
        $config['tenantName'] = $tenant->name;
        $visibility['tenantId'] = 'public';
        $visibility['tenantName'] = 'public';

        // Only add keys if they have values set in tenant config
        if ($tenantConfig?->has('app_url')) {
            $config['apiUrl'] = $tenantConfig->get('app_url');
            $visibility['apiUrl'] = 'public';
        }

        if ($tenantConfig?->has('frontend_url')) {
            $config['appUrl'] = $tenantConfig->get('frontend_url');
            $visibility['appUrl'] = 'public';
        }

        if ($tenantConfig?->has('app_name')) {
            $config['appName'] = $tenantConfig->get('app_name');
            $visibility['appName'] = 'public';
        }

        if ($tenantConfig?->has('pusher_app_key')) {
            $config['pusherAppKey'] = $tenantConfig->get('pusher_app_key');
            $visibility['pusherAppKey'] = 'public';
        }

        if ($tenantConfig?->has('pusher_app_cluster')) {
            $config['pusherAppCluster'] = $tenantConfig->get('pusher_app_cluster');
            $visibility['pusherAppCluster'] = 'public';
        }

        if ($tenantConfig?->has('recaptcha_site_key')) {
            $config['recaptchaGoogleSiteKey'] = $tenantConfig->get('recaptcha_site_key');
            $visibility['recaptchaGoogleSiteKey'] = 'public';
        }

        if ($tenantConfig?->has('internal_api_url')) {
            $config['internalApiUrl'] = $tenantConfig->get('internal_api_url');
            $visibility['internalApiUrl'] = 'protected';
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
        return 100; // High priority as Core module
    }
}
