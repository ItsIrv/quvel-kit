<?php

namespace Modules\Tenant\Pipes;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Context;
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
     * @param array<string, mixed> $tenantConfig The tenant configuration array
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

        // Handle X-Forwarded-Prefix for proxy setups
        $this->handleForwardedPrefix();

        if ($hasTimezoneChanges) {
            $this->refreshTimezone($config);
        }

        if ($hasLocaleChanges) {
            $this->refreshLocale($config);
        }

        if (isset($tenantConfig['recaptcha_secret_key'])) {
            $config->set('recaptcha_secret_key', $tenantConfig['recaptcha_secret_key']);
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
            if ($appUrl !== null) {
                $urlGenerator->useOrigin($appUrl);
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
            // Laravel automatically handles timezone for Carbon/Date through app.timezone config

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
     * Handle X-Forwarded-Prefix header for proxy setups.
     * This ensures the URL generator respects the proxy prefix path.
     */
    protected function handleForwardedPrefix(): void
    {
        try {
            // Only proceed if we have a request (not in testing/CLI contexts)
            if (!app()->bound('request')) {
                return;
            }

            $request = app('request');

            $prefix = $request->header('X-Forwarded-Prefix');
            if ($request->isFromTrustedProxy() && $prefix !== null) {
                $urlGenerator = app(UrlGenerator::class);
                /** @phpstan-ignore-next-line Using deprecated method until Laravel provides stable replacement */
                $urlGenerator->forceRootUrl(
                    $request->getSchemeAndHttpHost() . $prefix
                );

                if (app()->environment(['local', 'development', 'testing']) && app()->bound(CoreConfigPipeLogs::class)) {
                    app(CoreConfigPipeLogs::class)->forwardedPrefixApplied($prefix);
                }
            }
        } catch (\Exception $e) {
            if (app()->bound(CoreConfigPipeLogs::class)) {
                app(CoreConfigPipeLogs::class)->forwardedPrefixFailed($e->getMessage());
            }
        }
    }

    /**
     * Resolve core configuration for frontend TenantConfig interface.
     *
     * @param Tenant $tenant The tenant context
     * @param array<string, mixed> $tenantConfig The tenant configuration array
     * @return array<string, mixed> ['values' => array, 'visibility' => array] Resolved values and visibility
     */
    public function resolve(Tenant $tenant, array $tenantConfig): array
    {
        $values     = [];
        $visibility = [];

        if ($this->hasValue($tenantConfig, 'app_url')) {
            $values['apiUrl']     = $tenantConfig['app_url'];
            $visibility['apiUrl'] = 'public';
        }

        if ($this->hasValue($tenantConfig, 'frontend_url')) {
            $values['appUrl']     = $tenantConfig['frontend_url'];
            $visibility['appUrl'] = 'public';
        } elseif ($this->hasValue($tenantConfig, 'app_url')) {
            $values['appUrl']     = $tenantConfig['app_url'];
            $visibility['appUrl'] = 'public';
        }

        if ($this->hasValue($tenantConfig, 'app_name')) {
            $values['appName']     = $tenantConfig['app_name'];
            $visibility['appName'] = 'public';
        }

        if ($this->hasValue($tenantConfig, 'pusher_app_key')) {
            $values['pusherAppKey']     = $tenantConfig['pusher_app_key'];
            $visibility['pusherAppKey'] = 'public';
        }

        if ($this->hasValue($tenantConfig, 'pusher_app_cluster')) {
            $values['pusherAppCluster']     = $tenantConfig['pusher_app_cluster'];
            $visibility['pusherAppCluster'] = 'public';
        }

        if ($this->hasValue($tenantConfig, 'assets')) {
            $values['assets']     = $tenantConfig['assets'];
            $visibility['assets'] = 'public';
        }

        if ($this->hasValue($tenantConfig, 'meta')) {
            $values['meta']     = $tenantConfig['meta'];
            $visibility['meta'] = 'public';
        }

        return ['values' => $values, 'visibility' => $visibility];
    }
}
