<?php

namespace Modules\Core\Providers;

use Illuminate\Config\Repository;
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
        $config = app(Repository::class);

        return [
            'config'     => [
                'apiUrl'                 => $config->get('app.url'),
                'appUrl'                 => $config->get('frontend.url'),
                'appName'                => $config->get('app.name', 'Quvel Kit'),
                'tenantId'               => $tenant->public_id,
                'tenantName'             => $tenant->name,
                'pusherAppKey'           => $config->get('broadcasting.connections.pusher.key', ''),
                'pusherAppCluster'       => $config->get('broadcasting.connections.pusher.options.cluster', 'mt1'),
                'recaptchaGoogleSiteKey' => $config->get('recaptcha_site_key', '') ?? '',
                'internalApiUrl'         => $config->get('frontend.internal_api_url'),
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
