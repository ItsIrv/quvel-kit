<?php

namespace Modules\Tenant\Pipes;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Session\Session;
use Illuminate\Session\SessionManager;
use Modules\Tenant\Contracts\ConfigurationPipeInterface;
use Modules\Tenant\Models\Tenant;

/**
 * Handles session configuration for tenants.
 * Octane-safe: No static state needed.
 */
class SessionConfigPipe implements ConfigurationPipeInterface
{
    /**
     * Apply session configuration.
     */
    public function handle(Tenant $tenant, ConfigRepository $config, array $tenantConfig, callable $next): mixed
    {
        // Session configuration
        $hasSessionChanges = false;

        if (isset($tenantConfig['session_driver'])) {
            $driver = $tenantConfig['session_driver'];
            $config->set('session.driver', $driver);
            $hasSessionChanges = true;

            if (app()->environment(['local', 'development', 'testing'])) {
                logger()->debug("[Tenant] Set session driver: {$driver}", [
                    'tenant_id' => $tenant->public_id,
                ]);
            }
        }

        if (isset($tenantConfig['session_lifetime'])) {
            $lifetime = $tenantConfig['session_lifetime'];
            $config->set('session.lifetime', $lifetime);
            $hasSessionChanges = true;

            if (app()->environment(['local', 'development', 'testing'])) {
                logger()->debug("[Tenant] Set session lifetime: {$lifetime} minutes", [
                    'tenant_id' => $tenant->public_id,
                ]);
            }
        }

        if (isset($tenantConfig['session_encrypt'])) {
            $encrypt = $tenantConfig['session_encrypt'] ? 'true' : 'false';
            $config->set('session.encrypt', $tenantConfig['session_encrypt']);
            $hasSessionChanges = true;

            if (app()->environment(['local', 'development', 'testing'])) {
                logger()->debug("[Tenant] Set session encryption: {$encrypt}", [
                    'tenant_id' => $tenant->public_id,
                ]);
            }
        }

        if (isset($tenantConfig['session_path'])) {
            $path = $tenantConfig['session_path'];
            $config->set('session.path', $path);
            $hasSessionChanges = true;

            if (app()->environment(['local', 'development', 'testing'])) {
                logger()->debug("[Tenant] Set session path: {$path}", [
                    'tenant_id' => $tenant->public_id,
                ]);
            }
        }

        if (isset($tenantConfig['session_domain'])) {
            $domain = $tenantConfig['session_domain'];
            $config->set('session.domain', $domain);
            $hasSessionChanges = true;

            if (app()->environment(['local', 'development', 'testing'])) {
                logger()->debug("[Tenant] Set session domain: {$domain}", [
                    'tenant_id' => $tenant->public_id,
                ]);
            }
        }

        // Always set a tenant-specific session cookie name if not overridden
        if (isset($tenantConfig['session_cookie'])) {
            $cookie = $tenantConfig['session_cookie'];
            $config->set('session.cookie', $cookie);
            $hasSessionChanges = true;

            if (app()->environment(['local', 'development', 'testing'])) {
                logger()->debug("[Tenant] Set custom session cookie name: {$cookie}", [
                    'tenant_id' => $tenant->public_id,
                ]);
            }
        } else {
            // Default to tenant-specific cookie name
            $cookie = "tenant_{$tenant->id}_session";
            $config->set('session.cookie', $cookie);
            $hasSessionChanges = true;

            if (app()->environment(['local', 'development', 'testing'])) {
                logger()->debug("[Tenant] Set default session cookie name: {$cookie}", [
                    'tenant_id' => $tenant->public_id,
                ]);
            }
        }

        // Set the session connection to match the database connection if using database driver
        if ($config->get('session.driver') === 'database') {
            $dbConnection = $config->get('database.default');
            $config->set('session.connection', $dbConnection);
            $hasSessionChanges = true;

            if (app()->environment(['local', 'development', 'testing'])) {
                logger()->debug("[Tenant] Set session database connection to match tenant database: {$dbConnection}", [
                    'tenant_id' => $tenant->public_id,
                ]);
            }
        }

        // Apply the changes to the actual resources
        if ($hasSessionChanges) {
            if (app()->environment(['local', 'development', 'testing'])) {
                logger()->debug("[Tenant] Applying session configuration changes", [
                    'tenant_id'     => $tenant->public_id,
                    'changes_count' => count(array_intersect_key($tenantConfig, array_flip($this->handles()))),
                ]);
            }
            $this->rebindSessionManager($tenant->public_id);
        } else {
            if (app()->environment(['local', 'development', 'testing'])) {
                logger()->debug("[Tenant] No session configuration changes to apply", [
                    'tenant_id' => $tenant->public_id,
                ]);
            }
        }

        return $next([
            'tenant'       => $tenant,
            'config'       => $config,
            'tenantConfig' => $tenantConfig,
        ]);
    }

    /**
     * Rebind the session manager with new configuration.
     *
     * @param string $tenantId The tenant's public ID for logging
     */
    protected function rebindSessionManager(string $tenantId = ''): void
    {
        if (app()->bound(SessionManager::class)) {
            try {
                $startTime = microtime(true);

                app()->extend(SessionManager::class, function ($sessionManager, $app) {
                    return new SessionManager($app);
                });

                app()->forgetInstance(SessionManager::class);
                app()->forgetInstance(Session::class);

                $duration = round((microtime(true) - $startTime) * 1000, 2);

                if (app()->environment(['local', 'development', 'testing'])) {
                    logger()->debug("[Tenant] Rebound session manager with new configuration", [
                        'tenant_id'   => $tenantId,
                        'duration_ms' => $duration,
                    ]);
                }
            } catch (\Exception $e) {
                logger()->error("[Tenant] Failed to rebind session manager: {$e->getMessage()}", [
                    'tenant_id' => $tenantId,
                    'exception' => get_class($e),
                    'file'      => $e->getFile(),
                    'line'      => $e->getLine(),
                ]);
            }
        } else {
            if (app()->environment(['local', 'development', 'testing'])) {
                logger()->debug("[Tenant] SessionManager not bound in container, skipping rebind", [
                    'tenant_id' => $tenantId,
                ]);
            }
        }
    }

    /**
     * Reset session resources.
     * Octane-safe: No static state to clean up.
     */
    public static function resetResources(): void
    {
        if (app()->bound(SessionManager::class)) {
            try {
                $startTime = microtime(true);

                app()->extend(SessionManager::class, function ($sessionManager, $app) {
                    return new SessionManager($app);
                });

                app()->forgetInstance(SessionManager::class);
                app()->forgetInstance(Session::class);

                $duration = round((microtime(true) - $startTime) * 1000, 2);

                if (app()->environment(['local', 'development', 'testing'])) {
                    logger()->debug("[Tenant] Reset session manager with current configuration", [
                        'duration_ms' => $duration,
                    ]);
                }
            } catch (\Exception $e) {
                logger()->error("[Tenant] Failed to reset session manager: {$e->getMessage()}", [
                    'exception' => get_class($e),
                    'file'      => $e->getFile(),
                    'line'      => $e->getLine(),
                ]);
            }
        } else {
            if (app()->environment(['local', 'development', 'testing'])) {
                logger()->debug("[Tenant] No SessionManager bound in container during reset");
            }
        }
    }

    public function handles(): array
    {
        return [
            'session_driver',
            'session_lifetime',
            'session_encrypt',
            'session_path',
            'session_domain',
            'session_cookie',
        ];
    }

    public function priority(): int
    {
        return 83;
    }
}
