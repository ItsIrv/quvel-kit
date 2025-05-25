<?php

namespace Modules\Tenant\database\seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Tenant\database\factories\DynamicTenantConfigFactory;
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

        // Create different tier tenants to demonstrate the system

        // Basic Tier - Shared everything, minimal config
        $basicTenant = $this->createTenant(
            'basic.example.com',
            'Basic Tier Tenant',
            null, // Tier is stored in config now
            DynamicTenantConfigFactory::createBasicTier(
                domain: 'basic.example.com',
                appName: 'Basic QuVel App',
                mailFromName: 'Basic Support',
                mailFromAddress: 'support@basic.example.com',
            )->toArray(),
        );

        // Standard Tier - Shared database, dedicated cache
        $standardTenant = $this->createTenant(
            'standard.example.com',
            'Standard Tier Tenant',
            null, // Tier is stored in config now
            DynamicTenantConfigFactory::createStandardTier(
                domain: 'standard.example.com',
                appName: 'Standard QuVel App',
                mailFromName: 'Standard Support',
                mailFromAddress: 'support@standard.example.com',
            )->toArray(),
        );

        // Premium Tier - Dedicated database and cache
        $premiumTenant = $this->createTenant(
            $apiDomain,
            'Premium Tier Tenant - Main API',
            null, // Tier is stored in config now
            DynamicTenantConfigFactory::createPremiumTier(
                apiDomain: $apiDomain,
                appName: 'QuVel Premium',
                mailFromName: 'Premium Support',
                mailFromAddress: 'support@premium.example.com',
            )->toArray(),
        );

        // Enterprise Tier - Full isolation
        $enterpriseTenant = $this->createTenant(
            $lanApiDomain,
            'Enterprise Tier Tenant - LAN API',
            null, // Tier is stored in config now
            DynamicTenantConfigFactory::createEnterpriseTier(
                apiDomain: $lanApiDomain,
                appName: 'QuVel Enterprise',
                overrides: [
                    'capacitor_scheme'    => 'quvel',
                    'mail_from_address'   => 'enterprise@quvel.app',
                    'mail_from_name'      => 'QuVel Enterprise',
                    'session_lifetime'    => 240, // 4 hours for enterprise
                    'socialite_providers' => ['google', 'microsoft'],
                    'internal_api_url'    => 'http://api-lan:8000', // For SSR
                ],
            )->toArray(),
        );

        // Create frontend tenants (inherit parent config)
        $this->createTenant(
            $frontendDomain,
            'Premium Tenant - Frontend',
            null, // Tier inherited from parent
            null,
            $premiumTenant,
        );

        $this->createTenant(
            $lanFrontend,
            'Enterprise Tenant - Frontend',
            null,
            null,
            $enterpriseTenant,
        );

        // Docker internal domains
        $this->createTenant(
            'quvel-app',
            'Premium Tenant - Frontend Docker Internal',
            null,
            null,
            $premiumTenant,
        );

        $this->createTenant(
            'api-lan',
            'Enterprise Tenant - Frontend Docker Internal',
            null,
            null,
            $enterpriseTenant,
        );

        // Set tenant context to enterprise
        setTenant($enterpriseTenant->id);

        // Create test users for different tenants
        $this->createTenantUser($basicTenant, 'basic@quvel.app', 'Basic User');
        $this->createTenantUser($standardTenant, 'standard@quvel.app', 'Standard User');
        $this->createTenantUser($premiumTenant, 'premium@quvel.app', 'Premium User');
        $this->createTenantUser($enterpriseTenant, 'enterprise@quvel.app', 'Enterprise User');

        // Set tenant context back to premium for the rest of the seeders
        setTenant($premiumTenant->id);
    }

    /**
     * Create or update a tenant.
     */
    private function createTenant(
        string $domain,
        string $name,
        ?string $tier = null,
        ?array $config = null,
        ?Tenant $parent = null,
    ): Tenant {
        $data = [
            'name'      => $name,
            'domain'    => $domain,
            'config'    => $config,
            'parent_id' => $parent?->id,
        ];

        // Add tier if specified
        if ($tier !== null) {
            $data['tier'] = $tier;
        }

        return Tenant::updateOrCreate(
            ['domain' => $domain],
            Tenant::factory()->make($data)->toArray(),
        );
    }

    /**
     * Create a user for a tenant.
     *
     * @throws RandomException
     */
    private function createTenantUser(Tenant $tenant, string $email, string $name): void
    {
        // Set tenant context to create user in correct tenant
        $currentTenant = getTenant();
        setTenant($tenant->id);

        User::updateOrCreate(
            ['email' => $email],
            User::factory()->make([
                'name'              => $name,
                'tenant_id'         => $tenant->id,
                'password'          => Hash::make(config('quvel.default_password')),
                'email_verified_at' => now(),
                'avatar'            => 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . random_int(1, 100),
            ])->toArray(),
        );

        // Restore previous tenant context
        setTenant($currentTenant->id);
    }
}
