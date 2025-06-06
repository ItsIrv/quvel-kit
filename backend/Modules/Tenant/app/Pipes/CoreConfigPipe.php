<?php

namespace Modules\Tenant\Pipes;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Date;
use Modules\Tenant\Contracts\ConfigurationPipeInterface;
use Modules\Tenant\Logs\Pipes\CoreConfigPipeLogs;
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
        // Track changes to know which resources need refreshing
        $hasUrlChanges      = false;
        $hasTimezoneChanges = false;
        $hasLocaleChanges   = false;

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
            $hasUrlChanges = true;
        }
        if (isset($tenantConfig['app_timezone'])) {
            $config->set('app.timezone', $tenantConfig['app_timezone']);
            $hasTimezoneChanges = true;
        }

        // Localization
        if (isset($tenantConfig['app_locale'])) {
            $config->set('app.locale', $tenantConfig['app_locale']);
            $hasLocaleChanges = true;
        }
        if (isset($tenantConfig['app_fallback_locale'])) {
            $config->set('app.fallback_locale', $tenantConfig['app_fallback_locale']);
            $hasLocaleChanges = true;
        }

        // Frontend URLs
        if (isset($tenantConfig['frontend_url'])) {
            $config->set('frontend.url', $tenantConfig['frontend_url']);
            $hasUrlChanges = true;
        }
        if (isset($tenantConfig['internal_api_url'])) {
            $config->set('frontend.internal_api_url', $tenantConfig['internal_api_url']);
            $hasUrlChanges = true;
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

        // Pusher/Broadcasting configuration
        if (isset($tenantConfig['pusher_app_key'])) {
            $config->set('broadcasting.connections.pusher.key', $tenantConfig['pusher_app_key']);
        }
        if (isset($tenantConfig['pusher_app_secret'])) {
            $config->set('broadcasting.connections.pusher.secret', $tenantConfig['pusher_app_secret']);
        }
        if (isset($tenantConfig['pusher_app_id'])) {
            $config->set('broadcasting.connections.pusher.app_id', $tenantConfig['pusher_app_id']);
        }
        if (isset($tenantConfig['pusher_app_cluster'])) {
            $config->set('broadcasting.connections.pusher.options.cluster', $tenantConfig['pusher_app_cluster']);
        }

        // Laravel Context
        Context::add('tenant_id', $tenant->public_id);

        // Apply changes to actual resources
        if ($hasUrlChanges) {
            $this->refreshUrlGenerator($config);
        }

        if ($hasTimezoneChanges) {
            $this->refreshTimezone($config);
        }

        if ($hasLocaleChanges) {
            $this->refreshLocale($config);
        }

        return $next([
            'tenant'       => $tenant,
            'config'       => $config,
            'tenantConfig' => $tenantConfig,
        ]);
    }

    protected function refreshUrlGenerator(ConfigRepository $config): void
    {
        try {
            $urlGenerator = app(UrlGenerator::class);
            $appUrl       = $config->get('app.url');
            if ($appUrl) {
                $urlGenerator->forceRootUrl($appUrl);
            }

            if (app()->environment(['local', 'development', 'testing']) && app()->bound(CoreConfigPipeLogs::class)) {
                app(CoreConfigPipeLogs::class)->urlGeneratorUpdated($appUrl);
            }
        } catch (\Exception $e) {
            if (app()->bound(CoreConfigPipeLogs::class)) {
                app(CoreConfigPipeLogs::class)->urlGeneratorFailed($e->getMessage());
            }
        }
    }

    protected function refreshTimezone(ConfigRepository $config): void
    {
        try {
            $timezone = $config->get('app.timezone');
            date_default_timezone_set($timezone);
            Date::setFallbackTimezone($timezone);

            if (app()->environment(['local', 'development', 'testing']) && app()->bound(CoreConfigPipeLogs::class)) {
                app(CoreConfigPipeLogs::class)->timezoneUpdated($timezone);
            }
        } catch (\Exception $e) {
            if (app()->bound(CoreConfigPipeLogs::class)) {
                app(CoreConfigPipeLogs::class)->timezoneFailed($e->getMessage());
            }
        }
    }

    protected function refreshLocale(ConfigRepository $config): void
    {
        try {
            $locale = $config->get('app.locale');
            App::setLocale($locale);
            app()->forgetInstance(Translator::class);

            if (app()->environment(['local', 'development', 'testing']) && app()->bound(CoreConfigPipeLogs::class)) {
                app(CoreConfigPipeLogs::class)->localeUpdated($locale);
            }
        } catch (\Exception $e) {
            if (app()->bound(CoreConfigPipeLogs::class)) {
                app(CoreConfigPipeLogs::class)->localeFailed($e->getMessage());
            }
        }
    }

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
            'pusher_app_key',
            'pusher_app_secret',
            'pusher_app_id',
            'pusher_app_cluster',
        ];
    }

    public function priority(): int
    {
        return 100;
    }
}
