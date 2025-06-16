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
     * @param array<string, mixed> $tenantConfig The tenant configuration array
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
                if (!is_array($credentials)) {
                    continue;
                }
                if (isset($credentials['client_id'])) {
                    $config->set("services.$provider.client_id", (string) $credentials['client_id']);
                }
                if (isset($credentials['client_secret'])) {
                    $config->set("services.$provider.client_secret", (string) $credentials['client_secret']);
                }
                if (isset($credentials['redirect'])) {
                    $config->set("services.$provider.redirect", (string) $credentials['redirect']);
                } else {
                    // Default redirect URL based on tenant's app URL
                    $appUrl = (string) ($tenantConfig['app_url'] ?? $config->get('app.url') ?? '');
                    $config->set("services.$provider.redirect", "$appUrl/auth/provider/$provider/callback");
                }
            }
        } elseif (isset($tenantConfig['socialite_providers']) && is_array($tenantConfig['socialite_providers'])) {
            // Fallback: if providers are listed but credentials not in tenant config, use environment
            foreach ($tenantConfig['socialite_providers'] as $provider) {
                $envPrefix    = strtoupper($provider);
                $clientId     = $config->get("services.{$provider}.client_id");
                $clientSecret = $config->get("services.{$provider}.client_secret");

                if ($clientId !== null && $clientSecret !== null) {
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
     * Resolve authentication configuration for frontend TenantConfig interface.
     *
     * @param Tenant $tenant The tenant context
     * @param array<string, mixed> $tenantConfig The tenant configuration array
     * @return array<string, mixed> ['values' => array, 'visibility' => array] Resolved values and visibility
     */
    public function resolve(Tenant $tenant, array $tenantConfig): array
    {
        $values     = [];
        $visibility = [];

        if ($this->hasValue($tenantConfig, 'socialite_providers')) {
            $values['socialiteProviders']     = $tenantConfig['socialite_providers'];
            $visibility['socialiteProviders'] = 'public';
        }

        return ['values' => $values, 'visibility' => $visibility];
    }
}
