<?php

namespace Modules\Tenant\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\ValueObjects\DynamicTenantConfig;
use Modules\Tenant\Enums\TenantConfigVisibility;

/**
 * Tenant cache resource that reads directly from tenant->config.
 * Used for the /tenant/cache endpoint to avoid reading from global config.
 *
 * @property string $id
 * @property string $public_id
 * @property string $name
 * @property string $domain
 * @property Tenant|null $parent
 * @property DynamicTenantConfig|null $config
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class TenantCacheResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->public_id,
            'name'       => $this->name,
            'domain'     => $this->domain,
            'parent_id'  => $this->parent->public_id ?? null,
            'config'     => $this->getFilteredConfig(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Get filtered config directly from tenant->config.
     * This method reads from the tenant's actual config, not from Laravel's global config.
     */
    private function getFilteredConfig(): array
    {
        $tenantConfig = $this->config;
        $enhancedConfig = new DynamicTenantConfig();

        // Core module config - read directly from tenant config
        $this->addCoreConfig($enhancedConfig, $tenantConfig);

        // Auth module config - read directly from tenant config
        $this->addAuthConfig($enhancedConfig, $tenantConfig);

        // Get protected config (public + protected visibility)
        $protectedConfig = $enhancedConfig->getProtectedConfig();

        // Build visibility array
        $visibility = [];
        foreach ($protectedConfig as $key => $value) {
            $visibility[$key] = $enhancedConfig->getVisibility($key)->value;
        }

        // Add __visibility key for frontend compatibility
        $protectedConfig['__visibility'] = $visibility;

        return $protectedConfig;
    }

    /**
     * Add core module configuration.
     */
    private function addCoreConfig(DynamicTenantConfig $enhancedConfig, ?DynamicTenantConfig $tenantConfig): void
    {
        // Core configuration - read from tenant config when available
        $enhancedConfig->set('apiUrl', $tenantConfig?->get('app_url', config('app.url')) ?? config('app.url'));
        $enhancedConfig->set('appUrl', $tenantConfig?->get('frontend_url', config('frontend.url')) ?? config('frontend.url'));
        $enhancedConfig->set('appName', $tenantConfig?->get('app_name', config('app.name', 'Quvel Kit')) ?? config('app.name', 'Quvel Kit'));
        $enhancedConfig->set('tenantId', $this->public_id);
        $enhancedConfig->set('tenantName', $this->name);

        // Pusher config from tenant config
        $enhancedConfig->set('pusherAppKey', $tenantConfig?->get('pusher_app_key', '') ?? '');
        $enhancedConfig->set('pusherAppCluster', $tenantConfig?->get('pusher_app_cluster', 'mt1') ?? 'mt1');

        // reCAPTCHA config from tenant config
        $enhancedConfig->set('recaptchaGoogleSiteKey', $tenantConfig?->get('recaptcha_site_key', '') ?? '');

        // Additional Core module specific configs
        $enhancedConfig->set('internalApiUrl', $tenantConfig?->get('internal_api_url', config('frontend.internal_api_url')) ?? config('frontend.internal_api_url'));

        // Set visibility for core config
        $enhancedConfig->setVisibility('apiUrl', TenantConfigVisibility::PUBLIC);
        $enhancedConfig->setVisibility('appUrl', TenantConfigVisibility::PUBLIC);
        $enhancedConfig->setVisibility('appName', TenantConfigVisibility::PUBLIC);
        $enhancedConfig->setVisibility('tenantId', TenantConfigVisibility::PUBLIC);
        $enhancedConfig->setVisibility('tenantName', TenantConfigVisibility::PUBLIC);
        $enhancedConfig->setVisibility('pusherAppKey', TenantConfigVisibility::PUBLIC);
        $enhancedConfig->setVisibility('pusherAppCluster', TenantConfigVisibility::PUBLIC);
        $enhancedConfig->setVisibility('recaptchaGoogleSiteKey', TenantConfigVisibility::PUBLIC);
        $enhancedConfig->setVisibility('internalApiUrl', TenantConfigVisibility::PROTECTED);
    }

    /**
     * Add auth module configuration.
     */
    private function addAuthConfig(DynamicTenantConfig $enhancedConfig, ?DynamicTenantConfig $tenantConfig): void
    {
        // Auth configuration - read from tenant config when available
        $enhancedConfig->set('socialiteProviders', $tenantConfig?->get('socialite_providers', ['google']) ?? ['google']);
        $enhancedConfig->set('passwordMinLength', $tenantConfig?->get('password_min_length', 8) ?? 8);
        $enhancedConfig->set('sessionCookie', $tenantConfig?->get('session_cookie', 'quvel_session') ?? 'quvel_session');
        $enhancedConfig->set('twoFactorEnabled', $tenantConfig?->get('two_factor_enabled', false) ?? false);

        // Session lifetime if present
        if ($tenantConfig?->has('session_lifetime')) {
            $enhancedConfig->set('sessionLifetime', $tenantConfig->get('session_lifetime'));
        }

        // Set visibility for auth config
        $enhancedConfig->setVisibility('socialiteProviders', TenantConfigVisibility::PUBLIC);
        $enhancedConfig->setVisibility('passwordMinLength', TenantConfigVisibility::PUBLIC);
        $enhancedConfig->setVisibility('sessionCookie', TenantConfigVisibility::PROTECTED);
        $enhancedConfig->setVisibility('twoFactorEnabled', TenantConfigVisibility::PUBLIC);
        if ($tenantConfig?->has('session_lifetime')) {
            $enhancedConfig->setVisibility('sessionLifetime', TenantConfigVisibility::PROTECTED);
        }
    }
}
