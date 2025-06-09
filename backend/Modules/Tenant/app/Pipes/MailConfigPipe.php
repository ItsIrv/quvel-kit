<?php

namespace Modules\Tenant\Pipes;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Modules\Tenant\Pipes\BaseConfigurationPipe;
use Modules\Tenant\Models\Tenant;

/**
 * Handles mail configuration for tenants.
 */
class MailConfigPipe extends BaseConfigurationPipe
{
    /**
     * Apply mail configuration to Laravel config repository.
     *
     * @param Tenant $tenant The tenant context
     * @param ConfigRepository $config Laravel config repository
     * @param array $tenantConfig The tenant configuration array
     * @param callable $next The next pipe in the pipeline
     * @return mixed Result of calling $next()
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

    /**
     * Resolve mail configuration for frontend TenantConfig interface.
     *
     * @param Tenant $tenant The tenant context
     * @param array $tenantConfig The tenant configuration array
     * @return array Empty array - mail configuration is internal only
     */
    public function resolve(Tenant $tenant, array $tenantConfig): array
    {
        return [];
    }

    /**
     * Get the configuration keys that this pipe handles.
     *
     * @return array<string> Array of configuration keys
     */
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

    /**
     * Get the priority for this pipe (higher = runs first).
     *
     * @return int Priority value
     */
    public function priority(): int
    {
        return 70;
    }
}
