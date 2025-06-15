<?php

namespace Modules\Tenant\Database\Factories;

use Modules\Tenant\Enums\TenantConfigVisibility;
use Modules\Tenant\ValueObjects\DynamicTenantConfig;

class DynamicTenantConfigFactory
{
    /**
     * Create a basic tenant configuration for testing.
     */
    public static function createBasic(string $domain, string $appName): DynamicTenantConfig
    {
        return new DynamicTenantConfig([
            'app_name' => $appName,
            'app_url' => "https://{$domain}",
            'domain' => $domain,
        ], [
            'app_name' => TenantConfigVisibility::PUBLIC,
            'app_url' => TenantConfigVisibility::PUBLIC,
            'domain' => TenantConfigVisibility::PUBLIC,
        ]);
    }

    /**
     * Create an advanced tenant configuration for testing.
     */
    public static function createAdvanced(array $config = [], array $visibility = []): DynamicTenantConfig
    {
        $defaultConfig = [
            'app_name' => 'Test App',
            'app_url' => 'https://test.example.com',
            'database' => [
                'connection' => 'mysql',
                'host' => 'localhost',
            ],
        ];

        $defaultVisibility = [
            'app_name' => TenantConfigVisibility::PUBLIC,
            'app_url' => TenantConfigVisibility::PUBLIC,
            'database' => TenantConfigVisibility::PRIVATE,
        ];

        return new DynamicTenantConfig(
            array_merge($defaultConfig, $config),
            array_merge($defaultVisibility, $visibility)
        );
    }

    /**
     * Create a standard tenant configuration for testing.
     */
    public static function createStandard(?string $apiDomain = null, ?string $appName = null): DynamicTenantConfig
    {
        $domain = $apiDomain ?? 'standard.example.com';
        $name = $appName ?? 'Standard Test App';

        return new DynamicTenantConfig([
            'app_name' => $name,
            'app_url' => "https://{$domain}",
            'domain' => $domain,
            'api_domain' => $domain,
            'features' => ['feature1', 'feature2'],
        ], [
            'app_name' => TenantConfigVisibility::PUBLIC,
            'app_url' => TenantConfigVisibility::PUBLIC,
            'domain' => TenantConfigVisibility::PUBLIC,
            'api_domain' => TenantConfigVisibility::PUBLIC,
            'features' => TenantConfigVisibility::PROTECTED,
        ]);
    }
}
