<?php

namespace Modules\Tenant\Pipes;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Session\Session;
use Illuminate\Session\SessionManager;
use Modules\Tenant\Contracts\ConfigurationPipeInterface;
use Modules\Tenant\Logs\Pipes\SessionConfigPipeLogs;
use Modules\Tenant\Models\Tenant;

/**
 * Handles session configuration for tenants.
 * Octane-safe: No static state needed.
 */
class SessionConfigPipe implements ConfigurationPipeInterface
{
    /**
     * The logger instance.
     */
    protected SessionConfigPipeLogs $logger;

    /**
     * Create a new SessionConfigPipe instance.
     */
    public function __construct(SessionConfigPipeLogs $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Apply session configuration.
     */
    public function handle(Tenant $tenant, Repository $config, array $tenantConfig, callable $next): mixed
    {
        // Session configuration
        $hasSessionChanges = false;

        if (isset($tenantConfig['session_driver'])) {
            $driver = $tenantConfig['session_driver'];
            $config->set('session.driver', $driver);
            $hasSessionChanges = true;

            $this->logger->driverChanged($driver, $tenant->public_id);
        }

        if (isset($tenantConfig['session_lifetime'])) {
            $lifetime = $tenantConfig['session_lifetime'];
            $config->set('session.lifetime', $lifetime);
            $hasSessionChanges = true;

            $this->logger->lifetimeChanged($lifetime, $tenant->public_id);
        }

        if (isset($tenantConfig['session_encrypt'])) {
            $encrypt = $tenantConfig['session_encrypt'] ? 'true' : 'false';
            $config->set('session.encrypt', $tenantConfig['session_encrypt']);
            $hasSessionChanges = true;

            $this->logger->encryptionChanged($tenantConfig['session_encrypt'], $tenant->public_id);
        }

        if (isset($tenantConfig['session_path'])) {
            $path = $tenantConfig['session_path'];
            $config->set('session.path', $path);
            $hasSessionChanges = true;

            $this->logger->pathChanged($path, $tenant->public_id);
        }

        if (isset($tenantConfig['session_domain'])) {
            $domain = $tenantConfig['session_domain'];
            $config->set('session.domain', $domain);
            $hasSessionChanges = true;

            $this->logger->domainChanged($domain, $tenant->public_id);
        }

        // Always set a tenant-specific session cookie name if not overridden
        if (isset($tenantConfig['session_cookie'])) {
            $cookie = $tenantConfig['session_cookie'];
            $config->set('session.cookie', $cookie);
            $hasSessionChanges = true;

            $this->logger->cookieNameChanged($cookie, true, $tenant->public_id);
        } else {
            // Default to tenant-specific cookie name
            $cookie = "tenant_{$tenant->id}_session";
            $config->set('session.cookie', $cookie);
            $hasSessionChanges = true;

            $this->logger->cookieNameChanged($cookie, false, $tenant->public_id);
        }

        // Set the session connection to match the database connection if using database driver
        // if ($config->get('session.driver') === 'database') {
        //     $dbConnection = $config->get('database.default');
        //     $config->set('session.connection', $dbConnection);
        //     $hasSessionChanges = true;

        //     $this->logger->databaseConnectionChanged($dbConnection, $tenant->public_id);
        // }

        // Apply the changes to the actual resources
        if ($hasSessionChanges) {
            $this->logger->applyingChanges(
                $tenant->public_id,
                count(array_intersect_key($tenantConfig, array_flip($this->handles()))),
            );
            $this->rebindSessionManager($tenant->public_id);
        } else {
            $this->logger->noChangesToApply($tenant->public_id);
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

                $this->logger->sessionManagerRebound($tenantId, $duration);
            } catch (\Exception $e) {
                $this->logger->sessionManagerRebindFailed($tenantId, $e);
            }
        } else {
            $this->logger->sessionManagerNotBound($tenantId);
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

                self::getLogger()->sessionManagerReset($duration);
            } catch (\Exception $e) {
                self::getLogger()->sessionManagerResetFailed($e);
            }
        } else {
            self::getLogger()->sessionManagerNotBoundDuringReset();
        }
    }

    /**
     * Get the logger instance for static methods.
     */
    protected static function getLogger(): SessionConfigPipeLogs
    {
        return app(SessionConfigPipeLogs::class);
    }

    /**
     * The configuration keys that this pipe handles.
     */
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
