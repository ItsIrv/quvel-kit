<?php

namespace Modules\Tenant\Pipes;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Modules\Tenant\Models\Tenant;

/**
 * Handles logging configuration for tenants.
 */
class LoggingConfigPipe extends BaseConfigurationPipe
{
    /**
     * Apply logging configuration to Laravel config repository.
     *
     * @param Tenant $tenant The tenant context
     * @param ConfigRepository $config Laravel config repository
     * @param array<string, mixed> $tenantConfig The tenant configuration array
     * @param callable $next The next pipe in the pipeline
     * @return mixed Result of calling $next()
     */
    public function handle(Tenant $tenant, ConfigRepository $config, array $tenantConfig, callable $next): mixed
    {
        // Apply default log channel
        if (isset($tenantConfig['log_channel'])) {
            $config->set('logging.default', $tenantConfig['log_channel']);
        }

        // Configure deprecation logging
        if (isset($tenantConfig['log_deprecations_channel'])) {
            $config->set('logging.deprecations.channel', $tenantConfig['log_deprecations_channel']);
        }

        // Configure single file logging with tenant isolation
        if (isset($tenantConfig['log_single_path'])) {
            $config->set('logging.channels.single.path', $tenantConfig['log_single_path']);
        } else {
            $config->set('logging.channels.single.path', storage_path('logs/tenants/' . $tenant->public_id . '/laravel.log'));
        }

        // Configure daily file logging with tenant isolation
        if (isset($tenantConfig['log_daily_path'])) {
            $config->set('logging.channels.daily.path', $tenantConfig['log_daily_path']);
        } else {
            $config->set('logging.channels.daily.path', storage_path('logs/tenants/' . $tenant->public_id . '/laravel.log'));
        }

        if (isset($tenantConfig['log_daily_days'])) {
            $config->set('logging.channels.daily.days', $tenantConfig['log_daily_days']);
        }

        // Configure log level based on tenant tier
        if (isset($tenantConfig['log_level'])) {
            $level = $tenantConfig['log_level'];
            $config->set('logging.channels.single.level', $level);
            $config->set('logging.channels.daily.level', $level);
            $config->set('logging.channels.slack.level', $level);
            $config->set('logging.channels.stderr.level', $level);
        }

        // Configure Slack logging for important events
        if (isset($tenantConfig['log_slack_webhook_url'])) {
            $config->set('logging.channels.slack.url', $tenantConfig['log_slack_webhook_url']);

            if (isset($tenantConfig['log_slack_channel'])) {
                $config->set('logging.channels.slack.channel', $tenantConfig['log_slack_channel']);
            }

            if (isset($tenantConfig['log_slack_username'])) {
                $config->set('logging.channels.slack.username', $tenantConfig['log_slack_username']);
            }
        }

        // Configure Sentry for error tracking
        if (isset($tenantConfig['sentry_dsn'])) {
            $config->set('logging.channels.sentry', [
                'driver' => 'sentry',
                'level'  => $tenantConfig['sentry_level'] ?? 'error',
                'bubble' => true,
            ]);

            // Also set Sentry DSN in services config
            $config->set('services.sentry.dsn', $tenantConfig['sentry_dsn']);

            if (isset($tenantConfig['sentry_environment'])) {
                $config->set('services.sentry.environment', $tenantConfig['sentry_environment']);
            }
        }

        // Configure custom log channel for tenant
        if (isset($tenantConfig['log_custom_driver'])) {
            $config->set('logging.channels.tenant', [
                'driver' => $tenantConfig['log_custom_driver'],
                'path'   => $tenantConfig['log_custom_path'] ?? storage_path('logs/tenants/' . $tenant->public_id . '/custom.log'),
                'level'  => $tenantConfig['log_custom_level'] ?? 'info',
                'days'   => $tenantConfig['log_custom_days'] ?? 14,
            ]);
        }

        // Configure stack channel with tenant-specific channels
        if (isset($tenantConfig['log_stack_channels'])) {
            $config->set('logging.channels.stack.channels', $tenantConfig['log_stack_channels']);
        }

        // Pass to next pipe
        return $next([
            'tenant'       => $tenant,
            'config'       => $config,
            'tenantConfig' => $tenantConfig,
        ]);
    }

    /**
     * Resolve logging configuration for frontend TenantConfig interface.
     *
     * @param Tenant $tenant The tenant context
     * @param array<string, mixed> $tenantConfig The tenant configuration array
     * @return array<string, mixed> Empty array - logging configuration is internal only
     */
    public function resolve(Tenant $tenant, array $tenantConfig): array
    {
        return [];
    }

}
