<?php

namespace Modules\Tenant\Logs\Pipes;

use Modules\Core\Logs\BaseLogger;

/**
 * Logger for CoreConfigPipe operations
 */
class CoreConfigPipeLogs extends BaseLogger
{
    protected function getNamespace(): string
    {
        return 'tenant.pipes.core';
    }

    public function urlGeneratorUpdated(string $appUrl): void
    {
        $this->debug("Updated URL generator with new root URL: {$appUrl}");
    }

    public function urlGeneratorFailed(string $message): void
    {
        $this->error("Failed to refresh URL generator: {$message}");
    }

    public function timezoneUpdated(string $timezone): void
    {
        $this->debug("Updated application timezone to: {$timezone}");
    }

    public function timezoneFailed(string $message): void
    {
        $this->error("Failed to refresh timezone: {$message}");
    }

    public function localeUpdated(string $locale): void
    {
        $this->debug("Updated application locale to: {$locale}");
    }

    public function localeFailed(string $message): void
    {
        $this->error("Failed to refresh locale: {$message}");
    }

    public function resourcesReset(): void
    {
        $this->debug("Reset core resources to original configuration");
    }

    public function resourcesResetFailed(string $message): void
    {
        $this->error("Failed to reset core resources: {$message}");
    }

    public function forwardedPrefixApplied(string $prefix): void
    {
        $this->debug("Applied X-Forwarded-Prefix to URL generator: {$prefix}");
    }

    public function forwardedPrefixFailed(string $message): void
    {
        $this->error("Failed to apply X-Forwarded-Prefix: {$message}");
    }
}
