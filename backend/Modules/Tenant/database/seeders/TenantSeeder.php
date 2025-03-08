<?php

namespace Modules\Tenant\database\seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\database\factories\TenantConfigFactory;
use Modules\Tenant\Models\Tenant;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $apiDomain      = config('quvel.default_api_domain');
        $frontendDomain = str_replace('api.', '', $apiDomain);
        $lanApiDomain   = config('quvel.default_lan_domain');
        $lanFrontend    = str_replace('api.', '', $lanApiDomain);

        // Create API tenants
        $mainTenant = $this->createTenant(
            $apiDomain,
            'First Tenant - API',
            TenantConfigFactory::create(
                $apiDomain,
                'quvel-app',
            ),
        );

        $secondTenant = $this->createTenant(
            $lanApiDomain,
            'Second Tenant - API',
            TenantConfigFactory::create(
                $lanApiDomain,
                'api-lan',
                'QuVel - LAN',
            ),
        );

        // Create frontend tenants
        $this->createTenant(
            $frontendDomain,
            'First Tenant - Frontend',
            null,
            $mainTenant,
        );

        $this->createTenant(
            $lanFrontend,
            'Second Tenant - Frontend',
            null,
            $secondTenant,
        );

        $this->createTenant(
            'quvel-app',
            'First Tenant - Frontend Docker Internal',
            null,
            $mainTenant,
        );

        $this->createTenant(
            'api-lan',
            'Second Tenant - Frontend Docker Internal',
            null,
            $secondTenant,
        );

        // Set tenant context
        app(TenantContext::class)->set($secondTenant);

        // Create a test user for LAN Tenant
        $this->createTenantUser($secondTenant);
    }

    /**
     * Create or update a tenant.
     */
    private function createTenant(string $domain, string $name, ?array $config = null, ?Tenant $parent = null): Tenant
    {
        return Tenant::updateOrCreate(
            ['domain' => $domain],
            [
                'name'      => $name,
                'public_id' => Str::ulid()->toString(),
                'config'    => $config,
                'parent_id' => $parent?->id,
            ],
        );
    }

    /**
     * Create a user for a tenant.
     */
    private function createTenantUser(Tenant $tenant): void
    {
        User::updateOrCreate(
            ['email' => 'lan-user@quvel.app'],
            [
                'name'              => 'LAN Tenant User',
                'tenant_id'         => $tenant->id,
                'password'          => Hash::make(config('quvel.default_password')),
                'email_verified_at' => now(),
                'avatar'            => 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . rand(1, 100),
            ],
        );
    }
}
