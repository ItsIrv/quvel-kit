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
            $config->set('session.driver', $tenantConfig['session_driver']);
            $hasSessionChanges = true;
        }

        if (isset($tenantConfig['session_lifetime'])) {
            $config->set('session.lifetime', $tenantConfig['session_lifetime']);
            $hasSessionChanges = true;
        }

        if (isset($tenantConfig['session_encrypt'])) {
            $config->set('session.encrypt', $tenantConfig['session_encrypt']);
            $hasSessionChanges = true;
        }

        if (isset($tenantConfig['session_path'])) {
            $config->set('session.path', $tenantConfig['session_path']);
            $hasSessionChanges = true;
        }

        if (isset($tenantConfig['session_domain'])) {
            $config->set('session.domain', $tenantConfig['session_domain']);
            $hasSessionChanges = true;
        }

        // Always set a tenant-specific session cookie name if not overridden
        if (isset($tenantConfig['session_cookie'])) {
            $config->set('session.cookie', $tenantConfig['session_cookie']);
            $hasSessionChanges = true;
        } else {
            // Default to tenant-specific cookie name
            $config->set('session.cookie', "tenant_{$tenant->id}_session");
            $hasSessionChanges = true;
        }

        // Set the session connection to match the database connection if using database driver
        if ($config->get('session.driver') === 'database') {
            $dbConnection = $config->get('database.default');
            $config->set('session.connection', $dbConnection);
            $hasSessionChanges = true;

            if (app()->environment(['local', 'development', 'testing'])) {
                logger()->debug("[Tenant] Set session database connection to match tenant database: {$dbConnection}");
            }
        }

        // Apply the changes to the actual resources
        if ($hasSessionChanges) {
            $this->rebindSessionManager();
        }

        return $next([
            'tenant'       => $tenant,
            'config'       => $config,
            'tenantConfig' => $tenantConfig,
        ]);
    }

    protected function rebindSessionManager(): void
    {
        if (app()->bound(SessionManager::class)) {
            try {
                app()->extend(SessionManager::class, function ($sessionManager, $app) {
                    return new SessionManager($app);
                });

                app()->forgetInstance(SessionManager::class);
                app()->forgetInstance(Session::class);

                if (app()->environment(['local', 'development', 'testing'])) {
                    logger()->debug("[Tenant] Rebound session manager with new configuration");
                }
            } catch (\Exception $e) {
                logger()->error("[Tenant] Failed to rebind session manager: {$e->getMessage()}");
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
                app()->extend(SessionManager::class, function ($sessionManager, $app) {
                    return new SessionManager($app);
                });

                app()->forgetInstance(SessionManager::class);
                app()->forgetInstance(Session::class);

                if (app()->environment(['local', 'development', 'testing'])) {
                    logger()->debug("[Tenant] Reset session manager with current configuration");
                }
            } catch (\Exception $e) {
                logger()->error("[Tenant] Failed to reset session manager: {$e->getMessage()}");
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
