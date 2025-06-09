<?php

namespace Modules\Auth\Pipes;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Modules\Tenant\Pipes\BaseConfigurationPipe;
use Modules\Tenant\Models\Tenant;

/**
 * Handles authentication configuration for tenants.
 * This demonstrates how modules can add their own tenant configuration.
 */
class AuthConfigPipe extends BaseConfigurationPipe
{
    /**
     * Apply authentication configuration.
     */
    public function handle(Tenant $tenant, ConfigRepository $config, array $tenantConfig, callable $next): mixed
    {
        // OAuth/Socialite configuration
        if (isset($tenantConfig['socialite_providers'])) {
            $config->set('auth.socialite.providers', $tenantConfig['socialite_providers']);
        }
        if (isset($tenantConfig['socialite_nonce_ttl'])) {
            $config->set('auth.socialite.nonce_ttl', $tenantConfig['socialite_nonce_ttl']);
        }
        if (isset($tenantConfig['socialite_token_ttl'])) {
            $config->set('auth.socialite.token_ttl', $tenantConfig['socialite_token_ttl']);
        }
        if (isset($tenantConfig['hmac_secret_key'])) {
            $config->set('auth.socialite.hmac_secret', $tenantConfig['hmac_secret_key']);
        }

        // OAuth credentials
        foreach (($tenantConfig['oauth_credentials'] ?? []) as $provider => $credentials) {
            if (isset($credentials['client_id'])) {
                $config->set("services.$provider.client_id", $credentials['client_id']);
            }
            if (isset($credentials['client_secret'])) {
                $config->set("services.$provider.client_secret", $credentials['client_secret']);
            }
            if (isset($credentials['redirect'])) {
                $config->set("services.$provider.redirect", $credentials['redirect']);
            } else {
                // Default redirect URL based on tenant's app URL
                $appUrl = $tenantConfig['app_url'] ?? $config->get('app.url');
                $config->set("services.$provider.redirect", "$appUrl/auth/provider/$provider/callback");
            }
        }

        // Auth module specific settings
        if (isset($tenantConfig['disable_socialite'])) {
            $config->set('auth.disable_socialite', $tenantConfig['disable_socialite']);
        }
        if (isset($tenantConfig['verify_email_before_login'])) {
            $config->set('auth.verify_email_before_login', $tenantConfig['verify_email_before_login']);
        }
        if (isset($tenantConfig['password_min_length'])) {
            $config->set('auth.password_min_length', $tenantConfig['password_min_length']);
        }
        if (isset($tenantConfig['session_timeout'])) {
            $config->set('auth.session_timeout', $tenantConfig['session_timeout']);
        }

        return $next([
            'tenant'       => $tenant,
            'config'       => $config,
            'tenantConfig' => $tenantConfig,
        ]);
    }

    /**
     * Get the configuration keys this pipe handles.
     */
    public function handles(): array
    {
        return [
            'socialite_providers',
            'socialite_nonce_ttl',
            'socialite_token_ttl',
            'hmac_secret_key',
            'oauth_credentials',
            'disable_socialite',
            'verify_email_before_login',
            'password_min_length',
            'session_timeout',
        ];
    }

    /**
     * Get the priority for this pipe.
     */
    public function priority(): int
    {
        return 50; // Run after core tenant pipes
    }

    /**
     * Resolve configuration values for frontend TenantConfig interface.
     * Only returns fields that should be exposed to the frontend.
     */
    public function resolve(Tenant $tenant, array $tenantConfig): array
    {
        $resolved = [];

        // Only return socialiteProviders for frontend
        if (isset($tenantConfig['socialite_providers'])) {
            $resolved['socialiteProviders'] = $tenantConfig['socialite_providers'];
        }

        return $resolved;
    }
}
