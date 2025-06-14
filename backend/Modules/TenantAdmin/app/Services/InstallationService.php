<?php

namespace Modules\TenantAdmin\Services;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class InstallationService
{
    /**
     * Check if TenantAdmin is installed
     */
    public function isInstalled(): bool
    {
        return $this->hasEnvCredentials() || $this->hasDatabaseCredentials();
    }

    /**
     * Check if environment credentials exist
     */
    public function hasEnvCredentials(): bool
    {
        $username = config('tenantadmin.admin_username');
        $password = config('tenantadmin.admin_password');

        return ($username !== null && $username !== '' && $username !== '0') && ($password !== null && $password !== '' && $password !== '0');
    }

    /**
     * Check if database credentials exist
     */
    public function hasDatabaseCredentials(): bool
    {
        // First check if table exists
        if (!Schema::hasTable('tenant_admin_credentials')) {
            return false;
        }

        // Check if there are any records
        try {
            return DB::table('tenant_admin_credentials')->exists();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the installation method
     */
    public function getInstallationMethod(): ?string
    {
        if ($this->hasEnvCredentials()) {
            return 'env';
        }

        if ($this->hasDatabaseCredentials()) {
            return 'database';
        }

        return null;
    }

    /**
     * Create the credentials table
     */
    public function createCredentialsTable(): void
    {
        if (!Schema::hasTable('tenant_admin_credentials')) {
            Schema::create('tenant_admin_credentials', function ($table) {
                $table->id();
                $table->string('username')->unique();
                $table->string('password');
                $table->timestamps();
            });
        }
    }
}
