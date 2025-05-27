<?php

namespace Modules\Tenant\Pipes;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Date;
use Modules\Tenant\Contracts\ConfigurationPipeInterface;
use Modules\Tenant\Models\Tenant;

/**
 * Handles core Laravel configuration for tenants.
 * Octane-safe: Uses container for state storage instead of static properties.
 */
class CoreConfigPipe implements ConfigurationPipeInterface
{
    private const ORIGINAL_CONFIG_KEY = 'tenant.original_core_config';

    /**
     * Apply core configuration.
     */
    public function handle(Tenant $tenant, ConfigRepository $config, array $tenantConfig, callable $next): mixed
    {
        // Store original core config if not already stored (Octane-safe)
        if (!app()->has(self::ORIGINAL_CONFIG_KEY)) {
            app()->instance(self::ORIGINAL_CONFIG_KEY, [
                'app_name'             => $config->get('app.name'),
                'app_env'              => $config->get('app.env'),
                'app_key'              => $config->get('app.key'),
                'app_debug'            => $config->get('app.debug'),
                'app_url'              => $config->get('app.url'),
                'app_timezone'         => $config->get('app.timezone'),
                'app_locale'           => $config->get('app.locale'),
                'app_fallback_locale'  => $config->get('app.fallback_locale'),
                'frontend_url'         => $config->get('frontend.url'),
                'internal_api_url'     => $config->get('frontend.internal_api_url'),
                'capacitor_scheme'     => $config->get('frontend.capacitor_scheme'),
                'cors_allowed_origins' => $config->get('cors.allowed_origins'),
                'pusher_app_key'       => $config->get('broadcasting.connections.pusher.key'),
                'pusher_app_secret'    => $config->get('broadcasting.connections.pusher.secret'),
                'pusher_app_id'        => $config->get('broadcasting.connections.pusher.app_id'),
                'pusher_app_cluster'   => $config->get('broadcasting.connections.pusher.options.cluster'),
            ]);
        }

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

            if (app()->environment(['local', 'development', 'testing'])) {
                logger()->debug("[Tenant] Updated URL generator with new root URL: {$appUrl}");
            }
        } catch (\Exception $e) {
            logger()->error("[Tenant] Failed to refresh URL generator: {$e->getMessage()}");
        }
    }

    protected function refreshTimezone(ConfigRepository $config): void
    {
        try {
            $timezone = $config->get('app.timezone');
            date_default_timezone_set($timezone);
            Date::setFallbackTimezone($timezone);

            if (app()->environment(['local', 'development', 'testing'])) {
                logger()->debug("[Tenant] Updated application timezone to: {$timezone}");
            }
        } catch (\Exception $e) {
            logger()->error("[Tenant] Failed to refresh timezone: {$e->getMessage()}");
        }
    }

    protected function refreshLocale(ConfigRepository $config): void
    {
        try {
            $locale = $config->get('app.locale');
            App::setLocale($locale);
            app()->forgetInstance(Translator::class);

            if (app()->environment(['local', 'development', 'testing'])) {
                logger()->debug("[Tenant] Updated application locale to: {$locale}");
            }
        } catch (\Exception $e) {
            logger()->error("[Tenant] Failed to refresh locale: {$e->getMessage()}");
        }
    }

    /**
     * Reset resources after tenant context is done.
     * Octane-safe: Uses container instance instead of static property.
     */
    public static function resetResources(): void
    {
        if (app()->has(self::ORIGINAL_CONFIG_KEY)) {
            try {
                $originalConfig = app(self::ORIGINAL_CONFIG_KEY);

                // Restore original configuration
                config([
                    'app.name'                                        => $originalConfig['app_name'],
                    'app.env'                                         => $originalConfig['app_env'],
                    'app.key'                                         => $originalConfig['app_key'],
                    'app.debug'                                       => $originalConfig['app_debug'],
                    'app.url'                                         => $originalConfig['app_url'],
                    'app.timezone'                                    => $originalConfig['app_timezone'],
                    'app.locale'                                      => $originalConfig['app_locale'],
                    'app.fallback_locale'                             => $originalConfig['app_fallback_locale'],
                    'frontend.url'                                    => $originalConfig['frontend_url'],
                    'frontend.internal_api_url'                       => $originalConfig['internal_api_url'],
                    'frontend.capacitor_scheme'                       => $originalConfig['capacitor_scheme'],
                    'cors.allowed_origins'                            => $originalConfig['cors_allowed_origins'],
                    'broadcasting.connections.pusher.key'             => $originalConfig['pusher_app_key'],
                    'broadcasting.connections.pusher.secret'          => $originalConfig['pusher_app_secret'],
                    'broadcasting.connections.pusher.app_id'          => $originalConfig['pusher_app_id'],
                    'broadcasting.connections.pusher.options.cluster' => $originalConfig['pusher_app_cluster'],
                ]);

                $config   = app(ConfigRepository::class);
                $instance = new static();

                // Refresh with original config
                $instance->refreshUrlGenerator($config);
                $instance->refreshTimezone($config);
                $instance->refreshLocale($config);

                if (app()->environment(['local', 'development', 'testing'])) {
                    logger()->debug("[Tenant] Reset core resources to original configuration");
                }

                // Clean up the stored config
                app()->forgetInstance(self::ORIGINAL_CONFIG_KEY);
            } catch (\Exception $e) {
                logger()->error("[Tenant] Failed to reset core resources: {$e->getMessage()}");
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
