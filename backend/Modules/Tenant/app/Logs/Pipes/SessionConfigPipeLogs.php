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
    public function driverChanged(string $driver): void
    {
        $this->debug("Set session driver: {$driver}");
    }

    /**
     * Log a session lifetime change.
     */
    public function lifetimeChanged(int $lifetime): void
    {
        $this->debug("Set session lifetime: {$lifetime} minutes");
    }

    /**
     * Log a session encryption change.
     */
    public function encryptionChanged(bool $encrypt): void
    {
        $this->debug("Set session encryption: " . ($encrypt ? 'true' : 'false'));
    }

    /**
     * Log a session path change.
     */
    public function pathChanged(string $path): void
    {
        $this->debug("Set session path: {$path}");
    }

    /**
     * Log a session domain change.
     */
    public function domainChanged(string $domain): void
    {
        $this->debug("Set session domain: {$domain}");
    }

    /**
     * Log a session cookie name change.
     */
    public function cookieNameChanged(string $cookie, bool $isCustom): void
    {
        $prefix = $isCustom ? 'custom' : 'default';
        $this->debug("Set {$prefix} session cookie name: {$cookie}");
    }

    /**
     * Log a session database connection change.
     */
    public function databaseConnectionChanged(string $connection): void
    {
        $this->debug("Set session database connection to match tenant database: {$connection}");
    }

    /**
     * Log session configuration changes being applied.
     */
    public function applyingChanges(int $changesCount): void
    {
        $this->debug("Applying session configuration changes", [
            'changes_count' => $changesCount,
        ]);
    }

    /**
     * Log when no session configuration changes need to be applied.
     */
    public function noChangesToApply(): void
    {
        $this->debug("No session configuration changes to apply");
    }

    /**
     * Log session manager rebinding.
     */
    public function sessionManagerRebound(): void
    {
        $this->debug("Rebound session manager with new configuration");
    }

    /**
     * Log session manager rebinding failure.
     */
    public function sessionManagerRebindFailed(\Exception $exception): void
    {
        $this->error("Failed to rebind session manager: {$exception->getMessage()}", [
            'exception' => get_class($exception),
            'file'      => $exception->getFile(),
            'line'      => $exception->getLine(),
        ]);
    }

    /**
     * Log when session manager is not bound in container.
     */
    public function sessionManagerNotBound(): void
    {
        $this->debug("SessionManager not bound in container, skipping rebind");
    }

    /**
     * Log session manager reset.
     */
    public function sessionManagerReset(): void
    {
        $this->debug("Reset session manager with current configuration");
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
