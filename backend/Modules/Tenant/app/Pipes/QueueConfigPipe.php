<?php

namespace Modules\Tenant\Pipes;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Modules\Tenant\Models\Tenant;

/**
 * Handles queue configuration for tenants.
 */
class QueueConfigPipe extends BaseConfigurationPipe
{
    /**
     * Apply queue configuration to Laravel config repository.
     *
     * @param Tenant $tenant The tenant context
     * @param ConfigRepository $config Laravel config repository
     * @param array<string, mixed> $tenantConfig The tenant configuration array
     * @param callable $next The next pipe in the pipeline
     * @return mixed Result of calling $next()
     */
    public function handle(Tenant $tenant, ConfigRepository $config, array $tenantConfig, callable $next): mixed
    {
        // Apply tenant-specific queue configuration
        if (isset($tenantConfig['queue_default'])) {
            $config->set('queue.default', $tenantConfig['queue_default']);
        }

        // Configure queue connection settings
        if (isset($tenantConfig['queue_connection'])) {
            $connection = $tenantConfig['queue_connection'];

            // Database queue configuration
            if ($connection === 'database' && isset($tenantConfig['queue_database_table'])) {
                $config->set('queue.connections.database.table', $tenantConfig['queue_database_table']);
                $config->set('queue.connections.database.queue', $tenantConfig['queue_name'] ?? 'default');
                $config->set('queue.connections.database.retry_after', $tenantConfig['queue_retry_after'] ?? 90);
            }

            // Redis queue configuration
            if ($connection === 'redis') {
                $config->set('queue.connections.redis.queue', $tenantConfig['queue_name'] ?? 'default');
                $config->set('queue.connections.redis.retry_after', $tenantConfig['queue_retry_after'] ?? 90);

                // Use tenant-specific Redis database if configured
                if (isset($tenantConfig['redis_queue_database'])) {
                    $config->set('queue.connections.redis.connection', 'queue');
                    $config->set('database.redis.queue.database', $tenantConfig['redis_queue_database']);
                }
            }

            // SQS queue configuration for enterprise tenants
            if ($connection === 'sqs' && isset($tenantConfig['aws_sqs_queue'])) {
                $config->set('queue.connections.sqs.queue', $tenantConfig['aws_sqs_queue']);
                $config->set('queue.connections.sqs.region', $tenantConfig['aws_sqs_region'] ?? 'us-east-1');
                if (isset($tenantConfig['aws_sqs_key'])) {
                    $config->set('queue.connections.sqs.key', $tenantConfig['aws_sqs_key']);
                    $config->set('queue.connections.sqs.secret', $tenantConfig['aws_sqs_secret']);
                }
            }
        }

        // Configure failed jobs table
        if (isset($tenantConfig['queue_failed_table'])) {
            $config->set('queue.failed.table', $tenantConfig['queue_failed_table']);
        }

        // Pass to next pipe
        return $next([
            'tenant'       => $tenant,
            'config'       => $config,
            'tenantConfig' => $tenantConfig,
        ]);
    }

    /**
     * Resolve queue configuration for frontend TenantConfig interface.
     *
     * @param Tenant $tenant The tenant context
     * @param array<string, mixed> $tenantConfig The tenant configuration array
     * @return array<string, mixed> Empty array - queue configuration is internal only
     */
    public function resolve(Tenant $tenant, array $tenantConfig): array
    {
        return [];
    }

}
