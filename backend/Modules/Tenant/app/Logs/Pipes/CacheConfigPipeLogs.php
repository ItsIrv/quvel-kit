<?php

namespace Modules\Tenant\Logs\Pipes;

use Modules\Core\Logs\BaseLogger;

/**
 * Logger for CacheConfigPipe operations
 */
class CacheConfigPipeLogs extends BaseLogger
{
    protected function getNamespace(): string
    {
        return 'tenant.pipes.cache';
    }

    public function cacheManagerRebound(): void
    {
        $this->debug("Rebound cache manager with new configuration");
    }

    public function rebindFailed(string $message): void
    {
        $this->error("Failed to rebind cache manager: {$message}");
    }

    public function cacheManagerReset(): void
    {
        $this->debug("Reset cache manager to original configuration");
    }

    public function resetFailed(string $message): void
    {
        $this->error("Failed to reset cache manager: {$message}");
    }
}