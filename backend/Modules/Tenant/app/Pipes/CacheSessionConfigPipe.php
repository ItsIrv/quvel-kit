<?php

namespace Modules\Tenant\Pipes;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Modules\Tenant\Contracts\ConfigurationPipeInterface;
use Modules\Tenant\Models\Tenant;

/**
 * Handles cache and session configuration for tenants.
 * Supports shared cache/session with prefixes or isolated stores.
 */
class CacheSessionConfigPipe implements ConfigurationPipeInterface
{
    /**
     * Apply cache and session configuration.
     */
    public function handle(Tenant $tenant, ConfigRepository $config, array $tenantConfig, callable $next): mixed
    {
        // Cache configuration
        if (isset($tenantConfig['cache_store'])) {
            $config->set('cache.default', $tenantConfig['cache_store']);
        }
        
        // Always set a tenant-specific cache prefix if not overridden
        if (isset($tenantConfig['cache_prefix'])) {
            $config->set('cache.prefix', $tenantConfig['cache_prefix']);
        } else {
            // Default to tenant-specific prefix for isolation
            $config->set('cache.prefix', "tenant_{$tenant->id}_");
        }

        // Session configuration
        if (isset($tenantConfig['session_driver'])) {
            $config->set('session.driver', $tenantConfig['session_driver']);
        }
        if (isset($tenantConfig['session_lifetime'])) {
            $config->set('session.lifetime', $tenantConfig['session_lifetime']);
        }
        if (isset($tenantConfig['session_encrypt'])) {
            $config->set('session.encrypt', $tenantConfig['session_encrypt']);
        }
        if (isset($tenantConfig['session_path'])) {
            $config->set('session.path', $tenantConfig['session_path']);
        }
        if (isset($tenantConfig['session_domain'])) {
            $config->set('session.domain', $tenantConfig['session_domain']);
        }
        
        // Always set a tenant-specific session cookie name if not overridden
        if (isset($tenantConfig['session_cookie'])) {
            $config->set('session.cookie', $tenantConfig['session_cookie']);
        } else {
            // Default to tenant-specific cookie name
            $config->set('session.cookie', "tenant_{$tenant->id}_session");
        }

        // Redis configuration (if using Redis for cache/session)
        if (isset($tenantConfig['redis_client'])) {
            $config->set('database.redis.client', $tenantConfig['redis_client']);
        }
        if (isset($tenantConfig['redis_host'])) {
            $config->set('database.redis.default.host', $tenantConfig['redis_host']);
        }
        if (isset($tenantConfig['redis_password'])) {
            $config->set('database.redis.default.password', $tenantConfig['redis_password']);
        }
        if (isset($tenantConfig['redis_port'])) {
            $config->set('database.redis.default.port', $tenantConfig['redis_port']);
        }

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
            'cache_store',
            'cache_prefix',
            'session_driver',
            'session_lifetime',
            'session_encrypt',
            'session_path',
            'session_domain',
            'session_cookie',
            'redis_client',
            'redis_host',
            'redis_password',
            'redis_port',
        ];
    }

    /**
     * Get the priority for this pipe.
     */
    public function priority(): int
    {
        return 80;
    }
}