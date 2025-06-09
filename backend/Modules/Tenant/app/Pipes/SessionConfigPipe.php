<?php

namespace Modules\Tenant\Pipes;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Session\SessionManager;
use Modules\Tenant\Pipes\BaseConfigurationPipe;
use Modules\Tenant\Logs\Pipes\SessionConfigPipeLogs;
use Modules\Tenant\Models\Tenant;

/**
 * Handles session configuration for tenants.
 */
class SessionConfigPipe extends BaseConfigurationPipe
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
        } else {
            // Set tenant-safe session domain by default
            $sessionDomain = $this->extractSessionDomain($tenant, $tenantConfig);
            if ($sessionDomain) {
                $config->set('session.domain', $sessionDomain);
                $hasSessionChanges = true;

                $this->logger->domainChanged($sessionDomain);
            }
        }

        // Always set a tenant-specific session cookie name if not overridden
        $oldCookie = $config->get('session.cookie');
        if (isset($tenantConfig['session_cookie'])) {
            $cookie = $tenantConfig['session_cookie'];
            $config->set('session.cookie', $cookie);
            $this->logger->cookieNameChanged($cookie, true);
        } else {
            // For child tenants, use parent's public_id to ensure session sharing
            // This allows api.domain and app.domain to share the same session
            $tenantForCookie = $tenant->parent ?? $tenant;
            $cookie = "tenant_{$tenantForCookie->public_id}_session";
            $config->set('session.cookie', $cookie);

            $this->logger->cookieNameChanged($cookie, false);
        }

        // Log the change for debugging
        if ($oldCookie !== $cookie) {
            $hasSessionChanges = true;

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
     * Resolve session configuration values for frontend TenantConfig interface.
     * Only returns fields that should be exposed to the frontend.
     */
    public function resolve(Tenant $tenant, array $tenantConfig): array
    {
        $resolved = [];

        // Only return sessionCookie for frontend - no internal session config
        if (isset($tenantConfig['session_cookie'])) {
            $resolved['sessionCookie'] = $tenantConfig['session_cookie'];
        } else {
            // For child tenants, use parent's public_id to ensure session sharing
            // This allows api.domain and app.domain to share the same session
            $tenantForCookie = $tenant->parent ?? $tenant;
            $resolved['sessionCookie'] = "tenant_{$tenantForCookie->public_id}_session";
        }

        return $resolved;
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

    /**
     * Extract the session domain from tenant configuration.
     * This creates a tenant-safe session domain that works for both API and frontend.
     */
    protected function extractSessionDomain(Tenant $tenant, array $tenantConfig): ?string
    {
        // Try to get domain from tenant config first (app_url or frontend_url)
        $apiUrl      = $tenantConfig['app_url'] ?? null;
        $frontendUrl = $tenantConfig['frontend_url'] ?? null;

        // Use the API domain if available, otherwise fall back to tenant domain
        $domain = null;
        if ($apiUrl) {
            $domain = parse_url($apiUrl, PHP_URL_HOST);
        } elseif ($frontendUrl) {
            $domain = parse_url($frontendUrl, PHP_URL_HOST);
        } else {
            $domain = $tenant->domain;
        }

        if (!$domain) {
            return null;
        }

        // Extract root domain for session sharing between subdomains
        // Examples:
        // api.quvel.127.0.0.1.nip.io -> .quvel.127.0.0.1.nip.io
        // api.quvel-two.127.0.0.1.nip.io -> .quvel-two.127.0.0.1.nip.io
        $parts = explode('.', $domain);

        // If it's a subdomain (has more than 2 parts), remove the first part
        if (count($parts) > 2) {
            // Remove the first subdomain (e.g., 'api')
            array_shift($parts);
            $rootDomain = '.' . implode('.', $parts);
        } else {
            // For simple domains, use as-is with leading dot
            $rootDomain = '.' . $domain;
        }

        return $rootDomain;
    }
}
