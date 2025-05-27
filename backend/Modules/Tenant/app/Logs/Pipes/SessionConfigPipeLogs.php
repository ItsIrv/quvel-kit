<?php

namespace Modules\Tenant\Logs\Pipes;

use Modules\Core\Logs\BaseLogger;
use Illuminate\Log\LogManager;

/**
 * Logger for the SessionConfigPipe.
 * Provides structured logging methods for session configuration changes.
 */
class SessionConfigPipeLogs extends BaseLogger
{
    /**
     * The log context prefix.
     */
    protected string $contextPrefix = 'tenant_session';

    /**
     * Create a new logger instance.
     */
    public function __construct(LogManager $logger)
    {
        parent::__construct($logger);
    }

    /**
     * Log a session driver change.
     */
    public function driverChanged(string $driver, string $tenantId): void
    {
        $this->debug("Set session driver: {$driver}", [
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * Log a session lifetime change.
     */
    public function lifetimeChanged(int $lifetime, string $tenantId): void
    {
        $this->debug("Set session lifetime: {$lifetime} minutes", [
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * Log a session encryption change.
     */
    public function encryptionChanged(bool $encrypt, string $tenantId): void
    {
        $this->debug("Set session encryption: " . ($encrypt ? 'true' : 'false'), [
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * Log a session path change.
     */
    public function pathChanged(string $path, string $tenantId): void
    {
        $this->debug("Set session path: {$path}", [
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * Log a session domain change.
     */
    public function domainChanged(string $domain, string $tenantId): void
    {
        $this->debug("Set session domain: {$domain}", [
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * Log a session cookie name change.
     */
    public function cookieNameChanged(string $cookie, bool $isCustom, string $tenantId): void
    {
        $prefix = $isCustom ? 'custom' : 'default';
        $this->debug("Set {$prefix} session cookie name: {$cookie}", [
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * Log a session database connection change.
     */
    public function databaseConnectionChanged(string $connection, string $tenantId): void
    {
        $this->debug("Set session database connection to match tenant database: {$connection}", [
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * Log session configuration changes being applied.
     */
    public function applyingChanges(string $tenantId, int $changesCount): void
    {
        $this->debug("Applying session configuration changes", [
            'tenant_id'     => $tenantId,
            'changes_count' => $changesCount,
        ]);
    }

    /**
     * Log when no session configuration changes need to be applied.
     */
    public function noChangesToApply(string $tenantId): void
    {
        $this->debug("No session configuration changes to apply", [
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * Log session manager rebinding.
     */
    public function sessionManagerRebound(string $tenantId, float $durationMs): void
    {
        $this->debug("Rebound session manager with new configuration", [
            'tenant_id'   => $tenantId,
            'duration_ms' => $durationMs,
        ]);
    }

    /**
     * Log session manager rebinding failure.
     */
    public function sessionManagerRebindFailed(string $tenantId, \Exception $exception): void
    {
        $this->error("Failed to rebind session manager: {$exception->getMessage()}", [
            'tenant_id' => $tenantId,
            'exception' => get_class($exception),
            'file'      => $exception->getFile(),
            'line'      => $exception->getLine(),
        ]);
    }

    /**
     * Log when session manager is not bound in container.
     */
    public function sessionManagerNotBound(string $tenantId): void
    {
        $this->debug("SessionManager not bound in container, skipping rebind", [
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * Log session manager reset.
     */
    public function sessionManagerReset(float $durationMs): void
    {
        $this->debug("Reset session manager with current configuration", [
            'duration_ms' => $durationMs,
        ]);
    }

    /**
     * Log session manager reset failure.
     */
    public function sessionManagerResetFailed(\Exception $exception): void
    {
        $this->error("Failed to reset session manager: {$exception->getMessage()}", [
            'exception' => get_class($exception),
            'file'      => $exception->getFile(),
            'line'      => $exception->getLine(),
        ]);
    }

    /**
     * Log when session manager is not bound during reset.
     */
    public function sessionManagerNotBoundDuringReset(): void
    {
        $this->debug("No SessionManager bound in container during reset");
    }
}
