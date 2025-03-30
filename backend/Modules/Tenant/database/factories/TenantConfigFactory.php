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
        ?string $capacitorScheme = null,
        bool $toArray = true,
    ): array|TenantConfig {
        $config = new TenantConfig(
            apiUrl: "https://$apiDomain",
            appUrl: 'https://'.str_replace('api.', '', $apiDomain),
            appName: $appName,
            appEnv: $appEnv,
            internalApiUrl: $internalApiDomain ? "http://$internalApiDomain:8000" : null,
            debug: true,
            mailFromName: $mailFromName,
            mailFromAddress: $mailFromAddress,
            visibility: [
                'internal_api_url' => TenantConfigVisibility::PROTECTED,
                'api_url' => TenantConfigVisibility::PUBLIC,
                'app_url' => TenantConfigVisibility::PUBLIC,
                'app_name' => TenantConfigVisibility::PUBLIC,
            ],
            capacitorScheme: $capacitorScheme,
        );

        return $toArray ? $config->toArray() : $config;
    }
}
