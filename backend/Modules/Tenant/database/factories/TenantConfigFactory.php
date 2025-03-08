<?php

namespace Modules\Tenant\database\factories;

use Modules\Tenant\Enums\TenantConfigVisibility;
use Modules\Tenant\ValueObjects\TenantConfig;

class TenantConfigFactory
{
    /**
     * Generate a tenant configuration.
     */
    public static function create(
        string $apiDomain,
        ?string $internalApiDomain = null,
        string $appName = 'QuVel',
        string $appEnv = 'local',
        string $mailFromName = 'QuVel Support',
        string $mailFromAddress = 'support@quvel.app',
    ): array {
        return (new TenantConfig(
            apiUrl: "https://$apiDomain",
            internalApiUrl: $internalApiDomain ? "http://$internalApiDomain:8000" : null,
            appUrl: "https://" . str_replace('api.', '', $apiDomain),
            appName: $appName,
            appEnv: $appEnv,
            debug: true,
            mailFromName: $mailFromName,
            mailFromAddress: $mailFromAddress,
            visibility: [
                'app_name'         => TenantConfigVisibility::PUBLIC ,
                'app_url'          => TenantConfigVisibility::PUBLIC ,
                'api_url'          => TenantConfigVisibility::PUBLIC ,
                'internal_api_url' => TenantConfigVisibility::PROTECTED ,
            ],
        ))->toArray();
    }
}
