<?php

namespace Modules\Tenant\database\seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Tenant\database\factories\TenantConfigFactory;
use Modules\Tenant\Models\Tenant;
use Random\RandomException;

class TenantSeeder extends Seeder
{
    /**
     * @throws RandomException
     */
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
                apiDomain: $apiDomain,
                internalApiDomain: 'quvel-app',
                appName: 'QuVel Local',
            ),
        );

        $secondTenant = $this->createTenant(
            $lanApiDomain,
            'Second Tenant - API',
            TenantConfigFactory::create(
                apiDomain: $lanApiDomain,
                internalApiDomain: 'api-lan',
                appName: 'QuVel - LAN',
                capacitorScheme: 'quvel',
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
        setTenant($secondTenant->id);

        // Create a test user for LAN Tenant
        $this->createTenantUser($secondTenant);

        // Set tenant context back to main for the rest of the seeders
        setTenant($mainTenant->id);
    }

    /**
     * Create or update a tenant.
     */
    private function createTenant(string $domain, string $name, ?array $config = null, ?Tenant $parent = null): Tenant
    {
        return Tenant::updateOrCreate(
            ['domain' => $domain],
            Tenant::factory()->make([
                'name'      => $name,
                'domain'    => $domain,
                'config'    => $config,
                'parent_id' => $parent?->id,
            ])->toArray(),
        );
    }

    /**
     * Create a user for a tenant.
     *
     * @throws RandomException
     */
    private function createTenantUser(Tenant $tenant): void
    {
        User::updateOrCreate(
            ['email' => 'lan@quvel.app'],
            User::factory()->make([
                'name'              => 'LAN Tenant User',
                'tenant_id'         => $tenant->id,
                'password'          => Hash::make(config('quvel.default_password')),
                'email_verified_at' => now(),
                'avatar'            => 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . random_int(1, 100),
            ])->toArray(),
        );
    }
}
