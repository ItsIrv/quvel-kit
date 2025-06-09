<?php

namespace Modules\Tenant\Pipes;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Modules\Tenant\Pipes\BaseConfigurationPipe;
use Modules\Tenant\Models\Tenant;

class FilesystemConfigPipe extends BaseConfigurationPipe
{
    public function handle(Tenant $tenant, ConfigRepository $config, array $tenantConfig, callable $next): mixed
    {
        // Apply default disk configuration
        if (isset($tenantConfig['filesystem_default'])) {
            $config->set('filesystems.default', $tenantConfig['filesystem_default']);
        }

        if (isset($tenantConfig['filesystem_cloud'])) {
            $config->set('filesystems.cloud', $tenantConfig['filesystem_cloud']);
        }

        // Configure local disk with tenant isolation
        if (isset($tenantConfig['filesystem_local_root'])) {
            $config->set('filesystems.disks.local.root', $tenantConfig['filesystem_local_root']);
        } else {
            // Default tenant-isolated local storage
            $config->set('filesystems.disks.local.root', storage_path('app/tenants/' . $tenant->public_id));
        }

        // Configure public disk with tenant isolation
        if (isset($tenantConfig['filesystem_public_root'])) {
            $config->set('filesystems.disks.public.root', $tenantConfig['filesystem_public_root']);
        } else {
            // Default tenant-isolated public storage
            $config->set('filesystems.disks.public.root', storage_path('app/public/tenants/' . $tenant->public_id));
            $config->set('filesystems.disks.public.url', config('app.url') . '/storage/tenants/' . $tenant->public_id);
        }

        // Configure S3 disk for tenant
        if (isset($tenantConfig['aws_s3_bucket'])) {
            $config->set('filesystems.disks.s3.bucket', $tenantConfig['aws_s3_bucket']);

            // Tenant-specific path prefix
            if (isset($tenantConfig['aws_s3_path_prefix'])) {
                $config->set('filesystems.disks.s3.path_prefix', $tenantConfig['aws_s3_path_prefix']);
            } else {
                $config->set('filesystems.disks.s3.path_prefix', 'tenants/' . $tenant->public_id);
            }

            // Optional tenant-specific AWS credentials
            if (isset($tenantConfig['aws_s3_key'])) {
                $config->set('filesystems.disks.s3.key', $tenantConfig['aws_s3_key']);
                $config->set('filesystems.disks.s3.secret', $tenantConfig['aws_s3_secret']);
            }

            if (isset($tenantConfig['aws_s3_region'])) {
                $config->set('filesystems.disks.s3.region', $tenantConfig['aws_s3_region']);
            }

            if (isset($tenantConfig['aws_s3_url'])) {
                $config->set('filesystems.disks.s3.url', $tenantConfig['aws_s3_url']);
            }
        }

        // Configure temporary disk with tenant isolation
        if (!isset($tenantConfig['disable_temp_isolation'])) {
            $config->set('filesystems.disks.temp', [
                'driver'     => 'local',
                'root'       => storage_path('app/temp/tenants/' . $tenant->public_id),
                'visibility' => 'private',
            ]);
        }

        // Pass to next pipe
        return $next([
            'tenant'       => $tenant,
            'config'       => $config,
            'tenantConfig' => $tenantConfig,
        ]);
    }

    public function handles(): array
    {
        return [
            'filesystem_default',
            'filesystem_cloud',
            'filesystem_local_root',
            'filesystem_public_root',
            'aws_s3_bucket',
            'aws_s3_path_prefix',
            'aws_s3_key',
            'aws_s3_secret',
            'aws_s3_region',
            'aws_s3_url',
            'disable_temp_isolation',
        ];
    }

    public function priority(): int
    {
        return 55; // Run after queue pipe
    }
}
