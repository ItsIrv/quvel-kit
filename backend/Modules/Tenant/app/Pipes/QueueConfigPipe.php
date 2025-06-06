<?php

namespace Modules\Tenant\Pipes;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Modules\Tenant\Contracts\ConfigurationPipeInterface;
use Modules\Tenant\Models\Tenant;

class QueueConfigPipe implements ConfigurationPipeInterface
{
    protected array $originalConfig = [];

    public function handle(Tenant $tenant, ConfigRepository $config, array $tenantConfig, callable $next): mixed
    {
        // Store original config for Octane reset
        $this->originalConfig = [
            'default' => $config->get('queue.default'),
            'connections' => $config->get('queue.connections'),
        ];

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
            'tenant' => $tenant,
            'config' => $config,
            'tenantConfig' => $tenantConfig,
        ]);
    }

    public function handles(): array
    {
        return [
            'queue_default',
            'queue_connection',
            'queue_database_table',
            'queue_name',
            'queue_retry_after',
            'queue_failed_table',
            'redis_queue_database',
            'aws_sqs_queue',
            'aws_sqs_region',
            'aws_sqs_key',
            'aws_sqs_secret',
        ];
    }

    public function priority(): int
    {
        return 65; // Run after Redis pipe but before Mail pipe
    }

    public function reset(ConfigRepository $config): void
    {
        // Reset to original configuration for Octane
        $config->set('queue.default', $this->originalConfig['default']);
        $config->set('queue.connections', $this->originalConfig['connections']);
    }
}
