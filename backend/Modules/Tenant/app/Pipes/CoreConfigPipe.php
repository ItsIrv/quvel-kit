<?php

namespace Modules\Tenant\Pipes;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Context;
use Modules\Tenant\Contracts\ConfigurationPipeInterface;
use Modules\Tenant\Models\Tenant;

/**
 * Handles core Laravel configuration for tenants.
 */
class CoreConfigPipe implements ConfigurationPipeInterface
{
    /**
     * Apply core configuration.
     */
    public function handle(Tenant $tenant, ConfigRepository $config, array $tenantConfig, callable $next): mixed
    {
        // App settings
        if (isset($tenantConfig['app_name'])) {
            $config->set('app.name', $tenantConfig['app_name']);
        }
        if (isset($tenantConfig['app_env'])) {
            $config->set('app.env', $tenantConfig['app_env']);
        }
        if (isset($tenantConfig['app_key'])) {
            $config->set('app.key', $tenantConfig['app_key']);
        }
        if (isset($tenantConfig['app_debug'])) {
            $config->set('app.debug', $tenantConfig['app_debug']);
        }
        if (isset($tenantConfig['app_url'])) {
            $config->set('app.url', $tenantConfig['app_url']);
            
            // Update URL generator
            $urlGenerator = app(UrlGenerator::class);
            $urlGenerator->forceRootUrl($tenantConfig['app_url']);
        }
        if (isset($tenantConfig['app_timezone'])) {
            $config->set('app.timezone', $tenantConfig['app_timezone']);
        }

        // Localization
        if (isset($tenantConfig['app_locale'])) {
            $config->set('app.locale', $tenantConfig['app_locale']);
        }
        if (isset($tenantConfig['app_fallback_locale'])) {
            $config->set('app.fallback_locale', $tenantConfig['app_fallback_locale']);
        }

        // Frontend URLs
        if (isset($tenantConfig['frontend_url'])) {
            $config->set('frontend.url', $tenantConfig['frontend_url']);
        }
        if (isset($tenantConfig['internal_api_url'])) {
            $config->set('frontend.internal_api_url', $tenantConfig['internal_api_url']);
        }
        if (isset($tenantConfig['capacitor_scheme'])) {
            $config->set('frontend.capacitor_scheme', $tenantConfig['capacitor_scheme']);
        }

        // CORS - if frontend URL is set
        if (isset($tenantConfig['app_url']) || isset($tenantConfig['frontend_url'])) {
            $allowedOrigins = [];
            if (isset($tenantConfig['app_url'])) {
                $allowedOrigins[] = $tenantConfig['app_url'];
            }
            if (isset($tenantConfig['frontend_url'])) {
                $allowedOrigins[] = $tenantConfig['frontend_url'];
            }
            $config->set('cors.allowed_origins', $allowedOrigins);
        }

        // Laravel Context
        Context::add('tenant_id', $tenant->public_id);

        return $next([
            'tenant' => $tenant,
            'config' => $config,
            'tenantConfig' => $tenantConfig,
        ]);
    }

    /**
     * Get the configuration keys this pipe handles.
     */
    public function handles(): array
    {
        return [
            'app_name',
            'app_env',
            'app_key',
            'app_debug',
            'app_url',
            'app_timezone',
            'app_locale',
            'app_fallback_locale',
            'frontend_url',
            'internal_api_url',
            'capacitor_scheme',
        ];
    }

    /**
     * Get the priority for this pipe.
     */
    public function priority(): int
    {
        return 100; // Run early
    }
}