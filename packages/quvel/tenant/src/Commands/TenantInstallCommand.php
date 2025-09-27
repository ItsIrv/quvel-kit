<?php

declare(strict_types=1);

namespace Quvel\Tenant\Commands;

use Illuminate\Console\Command;

class TenantInstallCommand extends Command
{
    protected $signature = 'tenant:install';
    protected $description = 'Install the Tenant package';

    public function handle(): int
    {
        $this->info('Installing Tenant package...');

        $this->call('vendor:publish', [
            '--tag' => 'tenant-config',
            '--force' => true,
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'tenant-migrations',
            '--force' => true,
        ]);

        $this->info('Tenant package installed successfully!');
        $this->info('Run: php artisan migrate');
        $this->info('Run: php artisan db:seed --class=TenantConfigSeeder');

        return 0;
    }
}