<?php

namespace Modules\Auth\Pipes;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Modules\Tenant\Pipes\BaseConfigurationPipe;
use Modules\Tenant\Models\Tenant;

/**
 * Handles authentication configuration for tenants.
 */
class AuthConfigPipe extends BaseConfigurationPipe
{
    /**
     * Apply authentication configuration to Laravel config repository.
     *
     * @param Tenant $tenant The tenant context
     * @param ConfigRepository $config Laravel config repository
     * @param array $tenantConfig The tenant configuration array
     * @param callable $next The next pipe in the pipeline
     * @return mixed Result of calling $next()
     */
    public function handle(Tenant $tenant, ConfigRepository $config, array $tenantConfig, callable $next): mixed
    {
        // OAuth/Socialite configuration
        if ($this->hasValue($tenantConfig, 'socialite_providers')) {
            $config->set('auth.socialite.providers', $tenantConfig['socialite_providers']);
        }
        if ($this->hasValue($tenantConfig, 'socialite_nonce_ttl')) {
            $config->set('auth.socialite.nonce_ttl', $tenantConfig['socialite_nonce_ttl']);
        }
        if ($this->hasValue($tenantConfig, 'socialite_token_ttl')) {
            $config->set('auth.socialite.token_ttl', $tenantConfig['socialite_token_ttl']);
        }
        if ($this->hasValue($tenantConfig, 'hmac_secret_key')) {
            $config->set('auth.socialite.hmac_secret', $tenantConfig['hmac_secret_key']);
        }

        // OAuth credentials - try tenant config first, fallback to environment
        if (isset($tenantConfig['oauth_credentials']) && is_array($tenantConfig['oauth_credentials'])) {
            foreach ($tenantConfig['oauth_credentials'] as $provider => $credentials) {
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
        } elseif (isset($tenantConfig['socialite_providers']) && is_array($tenantConfig['socialite_providers'])) {
            // Fallback: if providers are listed but credentials not in tenant config, use environment
            foreach ($tenantConfig['socialite_providers'] as $provider) {
                $envPrefix    = strtoupper($provider);
                $clientId     = env("{$envPrefix}_CLIENT_ID");
                $clientSecret = env("{$envPrefix}_CLIENT_SECRET");

                if ($clientId && $clientSecret) {
                    $config->set("services.$provider.client_id", $clientId);
                    $config->set("services.$provider.client_secret", $clientSecret);

                    // Set default redirect URL
                    $appUrl = $tenantConfig['app_url'] ?? $config->get('app.url');
                    $config->set("services.$provider.redirect", "$appUrl/auth/provider/$provider/callback");
                }
            }
        }

        // Auth module specific settings
        if ($this->hasValue($tenantConfig, 'disable_socialite')) {
            $config->set('auth.disable_socialite', $tenantConfig['disable_socialite']);
        }
        if ($this->hasValue($tenantConfig, 'verify_email_before_login')) {
            $config->set('auth.verify_email_before_login', $tenantConfig['verify_email_before_login']);
        }
        if ($this->hasValue($tenantConfig, 'password_min_length')) {
            $config->set('auth.password_min_length', $tenantConfig['password_min_length']);
        }
        if ($this->hasValue($tenantConfig, 'session_timeout')) {
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
     *
     * @return array<string> Array of configuration keys
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
     * Get the priority for this pipe (higher = runs first).
     *
     * @return int Priority value
     */
    public function priority(): int
    {
        return 50; // Run after core tenant pipes
    }

    /**
     * Resolve authentication configuration for frontend TenantConfig interface.
     *
     * @param Tenant $tenant The tenant context
     * @param array $tenantConfig The tenant configuration array
     * @return array Resolved configuration values for frontend
     */
    public function resolve(Tenant $tenant, array $tenantConfig): array
    {
        $resolved = [];

        if ($this->hasValue($tenantConfig, 'socialite_providers')) {
            $resolved['socialiteProviders'] = $tenantConfig['socialite_providers'];
        }

        return $resolved;
    }
}
