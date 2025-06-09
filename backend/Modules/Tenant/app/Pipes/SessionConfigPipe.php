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
     * Apply session configuration to Laravel config repository.
     *
     * @param Tenant $tenant The tenant context
     * @param Repository $config Laravel config repository
     * @param array $tenantConfig The tenant configuration array
     * @param callable $next The next pipe in the pipeline
     * @return mixed Result of calling $next()
     */
    public function handle(Tenant $tenant, Repository $config, array $tenantConfig, callable $next): mixed
    {
        $hasChanges = false;
        $oldCookie  = $config->get('session.cookie');

        // Apply session configuration directly
        if ($this->hasValue($tenantConfig, 'session_driver')) {
            $config->set('session.driver', $tenantConfig['session_driver']);
            $this->logger->driverChanged($tenantConfig['session_driver']);
            $hasChanges = true;
        }

        if ($this->hasValue($tenantConfig, 'session_lifetime')) {
            $config->set('session.lifetime', $tenantConfig['session_lifetime']);
            $this->logger->lifetimeChanged($tenantConfig['session_lifetime']);
            $hasChanges = true;
        }

        if ($this->hasValue($tenantConfig, 'session_encrypt')) {
            $config->set('session.encrypt', $tenantConfig['session_encrypt']);
            $this->logger->encryptionChanged($tenantConfig['session_encrypt']);
            $hasChanges = true;
        }

        if ($this->hasValue($tenantConfig, 'session_path')) {
            $config->set('session.path', $tenantConfig['session_path']);
            $this->logger->pathChanged($tenantConfig['session_path']);
            $hasChanges = true;
        }

        // Session domain
        if ($this->hasValue($tenantConfig, 'session_domain')) {
            $config->set('session.domain', $tenantConfig['session_domain']);
            $this->logger->domainChanged($tenantConfig['session_domain']);
            $hasChanges = true;
        } else {
            $sessionDomain = $this->extractSessionDomain($tenant, $tenantConfig);
            if ($sessionDomain) {
                $config->set('session.domain', $sessionDomain);
                $this->logger->domainChanged($sessionDomain);
                $hasChanges = true;
            }
        }

        // Session cookie - use same logic as resolve()
        $newCookie = $this->calculateSessionCookie($tenant, $tenantConfig);
        $config->set('session.cookie', $newCookie);
        $isExplicit = $this->hasValue($tenantConfig, 'session_cookie');
        $this->logger->cookieNameChanged($newCookie, $isExplicit);

        if ($oldCookie !== $newCookie) {
            $hasChanges = true;
            $this->logger->debug('Session cookie name changed', [
                'old_cookie' => $oldCookie,
                'new_cookie' => $newCookie,
            ]);
        }

        // Set the session connection to match the database connection if using database driver
        if ($config->get('session.driver') === 'database') {
            $dbConnection = $config->get('database.default');
            $config->set('session.connection', $dbConnection);
            $hasChanges = true;
            $this->logger->databaseConnectionChanged($dbConnection);
        }

        // Apply the configuration changes
        if ($hasChanges) {
            $this->logger->applyingChanges(count(array_intersect_key($tenantConfig, array_flip($this->handles()))));

            // Update session manager
            $this->updateSessionManager($config, $newCookie);
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
     * Resolve session configuration for frontend TenantConfig interface.
     *
     * @param Tenant $tenant The tenant context
     * @param array $tenantConfig The tenant configuration array
     * @return array Resolved configuration values for frontend
     */
    public function resolve(Tenant $tenant, array $tenantConfig): array
    {
        $resolved = [];

        if ($this->hasValue($tenantConfig, 'session_cookie')) {
            $resolved['sessionCookie'] = $tenantConfig['session_cookie'];
        } else {
            $tenantForCookie           = $tenant->parent ?? $tenant;
            $resolved['sessionCookie'] = "tenant_{$tenantForCookie->public_id}_session";
        }

        return $resolved;
    }

    /**
     * Calculate session cookie name using the same logic as resolve().
     *
     * @param Tenant $tenant The tenant context
     * @param array $tenantConfig The tenant configuration array
     * @return string The calculated session cookie name
     */
    protected function calculateSessionCookie(Tenant $tenant, array $tenantConfig): string
    {
        if ($this->hasValue($tenantConfig, 'session_cookie')) {
            return $tenantConfig['session_cookie'];
        } else {
            $tenantForCookie = $tenant->parent ?? $tenant;
            return "tenant_{$tenantForCookie->public_id}_session";
        }
    }

    /**
     * The configuration keys that this pipe handles.
     *
     * @return array<string> Array of configuration keys
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

    /**
     * Get the priority for this pipe (higher = runs first).
     *
     * @return int Priority value
     */
    public function priority(): int
    {
        return 10;
    }

    /**
     * Update the session manager with the new cookie name.
     *
     * @param Repository $config Laravel config repository
     * @param string $cookieName The new cookie name
     */
    protected function updateSessionManager(Repository $config, string $cookieName): void
    {
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
                    $driver->setName($cookieName);

                    $this->logger->debug('Updated session driver cookie name', [
                        'cookie_name' => $cookieName,
                    ]);
                }
            }
        }
    }

    /**
     * Extract the session domain from tenant configuration.
     *
     * @param Tenant $tenant The tenant context
     * @param array $tenantConfig The tenant configuration array
     * @return string|null The extracted session domain or null
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
