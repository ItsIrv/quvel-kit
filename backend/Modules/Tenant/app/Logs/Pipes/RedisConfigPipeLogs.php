<?php

namespace Modules\Tenant\Logs\Pipes;

use Modules\Core\Logs\BaseLogger;

/**
 * Logger for RedisConfigPipe operations
 */
class RedisConfigPipeLogs extends BaseLogger
{
    protected function getNamespace(): string
    {
        return 'tenant.pipes.redis';
    }

    public function connectionsRefreshed(): void
    {
        $this->debug("Refreshed Redis connections with new configuration");
    }

    public function connectionsFailed(string $message): void
    {
        $this->error("Failed to refresh Redis connections: {$message}");
    }

    public function connectionsReset(): void
    {
        $this->debug("Reset Redis connections with current configuration");
    }

    public function resetFailed(string $message): void
    {
        $this->error("Failed to reset Redis connections: {$message}");
    }
}
