<?php

namespace Modules\Tenant\Pipes;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Date;
use Modules\Tenant\Pipes\BaseConfigurationPipe;
use Modules\Tenant\Logs\Pipes\CoreConfigPipeLogs;
use Modules\Tenant\Models\Tenant;

/**
 * Handles core Laravel configuration for tenants.
 */
class CoreConfigPipe extends BaseConfigurationPipe
{
    /**
     * Apply core configuration to Laravel config repository.
     *
     * @param Tenant $tenant The tenant context
     * @param ConfigRepository $config Laravel config repository
     * @param array $tenantConfig The tenant configuration array
     * @param callable $next The next pipe in the pipeline
     * @return mixed Result of calling $next()
     */
    public function handle(Tenant $tenant, ConfigRepository $config, array $tenantConfig, callable $next): mixed
    {
        // Track changes to know which resources need refreshing
        $hasUrlChanges      = false;
        $hasTimezoneChanges = false;
        $hasLocaleChanges   = false;

        // App settings
        if ($this->hasValue($tenantConfig, 'app_name')) {
            $config->set('app.name', $tenantConfig['app_name']);
        }
        if ($this->hasValue($tenantConfig, 'app_env')) {
            $config->set('app.env', $tenantConfig['app_env']);
        }
        if ($this->hasValue($tenantConfig, 'app_key')) {
            $config->set('app.key', $tenantConfig['app_key']);
        }
        if ($this->hasValue($tenantConfig, 'app_debug')) {
            $config->set('app.debug', $tenantConfig['app_debug']);
        }
        if ($this->hasValue($tenantConfig, 'app_url')) {
            $config->set('app.url', $tenantConfig['app_url']);
            $hasUrlChanges = true;
        }
        if ($this->hasValue($tenantConfig, 'app_timezone')) {
            $config->set('app.timezone', $tenantConfig['app_timezone']);
            $hasTimezoneChanges = true;
        }

        // Localization
        if ($this->hasValue($tenantConfig, 'app_locale')) {
            $config->set('app.locale', $tenantConfig['app_locale']);
            $hasLocaleChanges = true;
        }
        if ($this->hasValue($tenantConfig, 'app_fallback_locale')) {
            $config->set('app.fallback_locale', $tenantConfig['app_fallback_locale']);
            $hasLocaleChanges = true;
        }

        // Frontend URLs
        if ($this->hasValue($tenantConfig, 'frontend_url')) {
            $config->set('frontend.url', $tenantConfig['frontend_url']);
            $hasUrlChanges = true;
        }
        if ($this->hasValue($tenantConfig, 'internal_api_url')) {
            $config->set('frontend.internal_api_url', $tenantConfig['internal_api_url']);
            $hasUrlChanges = true;
        }
        if ($this->hasValue($tenantConfig, 'capacitor_scheme')) {
            $config->set('frontend.capacitor_scheme', $tenantConfig['capacitor_scheme']);
        }

        // CORS - if frontend URL is set
        if ($this->hasValue($tenantConfig, 'app_url') || $this->hasValue($tenantConfig, 'frontend_url')) {
            $allowedOrigins = [];
            if ($this->hasValue($tenantConfig, 'app_url')) {
                $allowedOrigins[] = $tenantConfig['app_url'];
            }
            if ($this->hasValue($tenantConfig, 'frontend_url')) {
                $allowedOrigins[] = $tenantConfig['frontend_url'];
            }
            $config->set('cors.allowed_origins', $allowedOrigins);
        }

        // Pusher/Broadcasting configuration
        if ($this->hasValue($tenantConfig, 'pusher_app_key')) {
            $config->set('broadcasting.connections.pusher.key', $tenantConfig['pusher_app_key']);
        }
        if ($this->hasValue($tenantConfig, 'pusher_app_secret')) {
            $config->set('broadcasting.connections.pusher.secret', $tenantConfig['pusher_app_secret']);
        }
        if ($this->hasValue($tenantConfig, 'pusher_app_id')) {
            $config->set('broadcasting.connections.pusher.app_id', $tenantConfig['pusher_app_id']);
        }
        if ($this->hasValue($tenantConfig, 'pusher_app_cluster')) {
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

    /**
     * Get the configuration keys that this pipe handles.
     *
     * @return array<string> Array of configuration keys
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
            'pusher_app_key',
            'pusher_app_secret',
            'pusher_app_id',
            'pusher_app_cluster',
        ];
    }

    /**
     * Get the priority for this pipe (higher = runs first).
     *
     * @return int Priority value
     */
    public function priority(): int
    {
        return 100;
    }

    /**
     * Resolve core configuration for frontend TenantConfig interface.
     *
     * @param Tenant $tenant The tenant context
     * @param array $tenantConfig The tenant configuration array
     * @return array ['values' => array, 'visibility' => array] Resolved values and visibility
     */
    public function resolve(Tenant $tenant, array $tenantConfig): array
    {
        $values = [];
        $visibility = [];

        if ($this->hasValue($tenantConfig, 'app_url')) {
            $values['apiUrl'] = $tenantConfig['app_url'];
            $visibility['apiUrl'] = 'public';
        }
        
        if ($this->hasValue($tenantConfig, 'frontend_url')) {
            $values['appUrl'] = $tenantConfig['frontend_url'];
            $visibility['appUrl'] = 'public';
        } elseif ($this->hasValue($tenantConfig, 'app_url')) {
            $values['appUrl'] = $tenantConfig['app_url'];
            $visibility['appUrl'] = 'public';
        }
        
        if ($this->hasValue($tenantConfig, 'app_name')) {
            $values['appName'] = $tenantConfig['app_name'];
            $visibility['appName'] = 'public';
        }
        
        if ($this->hasValue($tenantConfig, 'pusher_app_key')) {
            $values['pusherAppKey'] = $tenantConfig['pusher_app_key'];
            $visibility['pusherAppKey'] = 'public';
        }
        
        if ($this->hasValue($tenantConfig, 'pusher_app_cluster')) {
            $values['pusherAppCluster'] = $tenantConfig['pusher_app_cluster'];
            $visibility['pusherAppCluster'] = 'public';
        }

        return ['values' => $values, 'visibility' => $visibility];
    }
}
