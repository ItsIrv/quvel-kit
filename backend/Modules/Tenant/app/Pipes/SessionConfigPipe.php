<?php

namespace Modules\Tenant\Pipes;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Session\SessionManager;
use Modules\Tenant\Contracts\ConfigurationPipeInterface;
use Modules\Tenant\Logs\Pipes\SessionConfigPipeLogs;
use Modules\Tenant\Models\Tenant;

/**
 * Handles session configuration for tenants.
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

            $this->logger->driverChanged($driver);
        }

        if (isset($tenantConfig['session_lifetime'])) {
            $lifetime = $tenantConfig['session_lifetime'];
            $config->set('session.lifetime', $lifetime);
            $hasSessionChanges = true;

            $this->logger->lifetimeChanged($lifetime);
        }

        if (isset($tenantConfig['session_encrypt'])) {
            $encrypt = $tenantConfig['session_encrypt'] ? 'true' : 'false';
            $config->set('session.encrypt', $tenantConfig['session_encrypt']);
            $hasSessionChanges = true;

            $this->logger->encryptionChanged($tenantConfig['session_encrypt']);
        }

        if (isset($tenantConfig['session_path'])) {
            $path = $tenantConfig['session_path'];
            $config->set('session.path', $path);
            $hasSessionChanges = true;

            $this->logger->pathChanged($path);
        }

        if (isset($tenantConfig['session_domain'])) {
            $domain = $tenantConfig['session_domain'];
            $config->set('session.domain', $domain);
            $hasSessionChanges = true;

            $this->logger->domainChanged($domain);
        }

        // Always set a tenant-specific session cookie name if not overridden
        $oldCookie = $config->get('session.cookie');
        if (isset($tenantConfig['session_cookie'])) {
            $cookie = $tenantConfig['session_cookie'];
            $config->set('session.cookie', $cookie);
            $hasSessionChanges = true;

            $this->logger->cookieNameChanged($cookie, true);
        } else {
            // Default to tenant-specific cookie name using public_id for security
            $cookie = "tenant_{$tenant->public_id}_session";
            $config->set('session.cookie', $cookie);
            $hasSessionChanges = true;

            $this->logger->cookieNameChanged($cookie, false);
        }

        // Log the change for debugging
        if ($oldCookie !== $cookie) {
            $this->logger->debug('Session cookie name changed', [
                'old_cookie' => $oldCookie,
                'new_cookie' => $cookie,
            ]);
        }

        // Set the session connection to match the database connection if using database driver
        if ($config->get('session.driver') === 'database') {
            $dbConnection = $config->get('database.default');
            $config->set('session.connection', $dbConnection);
            $hasSessionChanges = true;

            $this->logger->databaseConnectionChanged($dbConnection);
        }

        // Apply the configuration changes
        if ($hasSessionChanges) {
            $this->logger->applyingChanges(
                count(
                    array_intersect_key(
                        $tenantConfig,
                        array_flip($this->handles()),
                    ),
                ),
            );

            // Force the session manager to use the new configuration
            // This is necessary because the session might be lazily initialized
            if (app()->bound(SessionManager::class)) {
                // Get the current session manager
                $sessionManager = app(SessionManager::class);

                // If it's using the cookie session handler, update the cookie name
                if ($sessionManager && method_exists($sessionManager, 'driver')) {
                    /** @var \Illuminate\Session\Store $driver */
                    $driver = $sessionManager->driver();
                    if ($driver && method_exists($driver, 'setName')) {
                        $driver->setName($config->get('session.cookie'));

                        $this->logger->debug('Updated session driver cookie name', [
                            'cookie_name' => $config->get('session.cookie'),
                        ]);
                    }
                }
            }
        } else {
            $this->logger->noChangesToApply();
        }

        return $next([
            'tenant'       => $tenant,
            'config'       => $config,
            'tenantConfig' => $tenantConfig,
        ]);
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
        // Session configuration must be set very early, before any services
        // that might start or use sessions. This needs to run before
        // any other pipes that might trigger session initialization.
        return 10;
    }
}
