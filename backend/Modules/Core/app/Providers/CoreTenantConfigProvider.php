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
            'config' => [
                // Frontend service URLs and configuration
                'frontend_service_url' => config('frontend.url'),
                'frontend_internal_api_url' => config('frontend.internal_api_url'),
                
                // Any other Core module specific configs that need to be exposed
                'api_version' => config('app.api_version', 'v1'),
                'supported_locales' => config('app.supported_locales', ['en']),
            ],
            'visibility' => [
                'frontend_service_url' => 'protected',
                'frontend_internal_api_url' => 'protected',
                'api_version' => 'public',
                'supported_locales' => 'public',
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