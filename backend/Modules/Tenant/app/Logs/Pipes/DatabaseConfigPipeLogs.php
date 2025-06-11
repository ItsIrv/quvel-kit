<?php

namespace Modules\Tenant\Logs\Pipes;

use Modules\Core\Logs\BaseLogger;

/**
 * Logger for DatabaseConfigPipe operations
 */
class DatabaseConfigPipeLogs extends BaseLogger
{
    protected function getNamespace(): string
    {
        return 'tenant.pipes.database';
    }

    public function connectionSwitched(string $connection, string $tenantName): void
    {
        $this->debug("Switched database connection to {$connection} for tenant {$tenantName}");
    }

    public function connectionReset(string $connection): void
    {
        $this->debug("Reset database connection to original: {$connection}");
    }

    public function resetFailed(string $message): void
    {
        $this->error("Failed to reset database connection: {$message}");
    }
}
