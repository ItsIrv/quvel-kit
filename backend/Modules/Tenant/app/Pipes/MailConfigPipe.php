<?php

namespace Modules\Tenant\Pipes;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Modules\Tenant\Contracts\ConfigurationPipeInterface;
use Modules\Tenant\Models\Tenant;

/**
 * Handles mail configuration for tenants.
 * Octane-safe: No static state needed.
 */
class MailConfigPipe implements ConfigurationPipeInterface
{
    /**
     * Apply mail configuration.
     */
    public function handle(Tenant $tenant, ConfigRepository $config, array $tenantConfig, callable $next): mixed
    {
        // Mail driver
        if (isset($tenantConfig['mail_mailer'])) {
            $config->set('mail.default', $tenantConfig['mail_mailer']);
        }

        // SMTP settings
        if (isset($tenantConfig['mail_host'])) {
            $config->set('mail.mailers.smtp.host', $tenantConfig['mail_host']);
        }
        if (isset($tenantConfig['mail_port'])) {
            $config->set('mail.mailers.smtp.port', $tenantConfig['mail_port']);
        }
        if (isset($tenantConfig['mail_username'])) {
            $config->set('mail.mailers.smtp.username', $tenantConfig['mail_username']);
        }
        if (isset($tenantConfig['mail_password'])) {
            $config->set('mail.mailers.smtp.password', $tenantConfig['mail_password']);
        }
        if (isset($tenantConfig['mail_encryption'])) {
            $config->set('mail.mailers.smtp.encryption', $tenantConfig['mail_encryption']);
        }

        // From address/name (required for all tenants)
        if (isset($tenantConfig['mail_from_address'])) {
            $config->set('mail.from.address', $tenantConfig['mail_from_address']);
        }
        if (isset($tenantConfig['mail_from_name'])) {
            $config->set('mail.from.name', $tenantConfig['mail_from_name']);
        }

        return $next([
            'tenant'       => $tenant,
            'config'       => $config,
            'tenantConfig' => $tenantConfig,
        ]);
    }

    public function handles(): array
    {
        return [
            'mail_mailer',
            'mail_host',
            'mail_port',
            'mail_username',
            'mail_password',
            'mail_encryption',
            'mail_from_address',
            'mail_from_name',
        ];
    }

    public function priority(): int
    {
        return 70;
    }
}
