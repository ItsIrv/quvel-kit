<?php

namespace Modules\Tenant\database\seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Tenant\Database\Factories\TenantTemplateFactory;
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

        // Create simple tenants for development

        // Basic tenant with minimal config (using basic template)
        $basicTenant = $this->createTenantWithConfig(
            'api.quvel-two.127.0.0.1.nip.io',
            'Basic Tenant',
            TenantTemplateFactory::basic('api.quvel-two.127.0.0.1.nip.io'),
            [
                'app_name'          => 'Basic QuVel App',
                'mail_from_name'    => 'Basic Support',
                'mail_from_address' => 'support@quvel-two.127.0.0.1.nip.io',
            ],
        );

        // Child tenant for the basic tenant
        $this->createTenant(
            'quvel-two.127.0.0.1.nip.io',
            'Basic Frontend',
            null,
            $basicTenant,
        );

        // Main API tenant with basic config (using basic template)
        $mainTenant = $this->createTenantWithConfig(
            $apiDomain,
            'Main API Tenant',
            TenantTemplateFactory::basic($apiDomain),
            [
                'app_name'          => 'QuVel App',
                'mail_from_name'    => 'QuVel Support',
                'mail_from_address' => 'support@quvel.app',
            ],
        );

        // LAN API tenant with full isolation (using isolated template)
        $lanTenant = $this->createTenantWithConfig(
            $lanApiDomain,
            'LAN API Tenant',
            TenantTemplateFactory::isolated($lanApiDomain, [
                'internal_api_url' => 'http://api-lan:8000', // Infrastructure override for SSR
            ]),
            [
                'app_name'          => 'QuVel LAN',
                'capacitor_scheme'  => 'quvel',
                'mail_from_address' => 'lan@quvel.app',
                'mail_from_name'    => 'QuVel LAN',
            ],
        );

        // Create frontend tenants (inherit parent config)
        $this->createTenant(
            $frontendDomain,
            'Main Frontend',
            null,
            $mainTenant,
        );

        $this->createTenant(
            $lanFrontend,
            'LAN Frontend',
            null,
            $lanTenant,
        );

        // Docker internal domains
        $this->createTenant(
            'quvel-app',
            'Main Frontend Docker Internal',
            null,
            $mainTenant,
        );

        $this->createTenant(
            'api-lan',
            'LAN Frontend Docker Internal',
            null,
            $lanTenant,
        );

        // Set tenant context to LAN
        setTenantContext($lanTenant->id);

        // Create test users for different tenants
        $this->createTenantUser($basicTenant, 'basic@quvel.app', 'Basic User');
        $this->createTenantUser($mainTenant, 'main@quvel.app', 'Main User');
        $this->createTenantUser($lanTenant, 'lan@quvel.app', 'LAN User');

        // Set tenant context back to main for the rest of the seeders
        setTenantContext($mainTenant->id);
    }

    /**
     * Create or update a tenant.
     */
    private function createTenant(
        string $domain,
        string $name,
        ?array $config = null,
        ?Tenant $parent = null,
    ): Tenant {
        $data = [
            'name'      => $name,
            'domain'    => $domain,
            'config'    => $config,
            'parent_id' => $parent?->id,
        ];

        return Tenant::updateOrCreate(
            ['domain' => $domain],
            Tenant::factory()->make($data)->toArray(),
        );
    }

    /**
     * Create or update a tenant with configuration template and parameters.
     */
    private function createTenantWithConfig(
        string $domain,
        string $name,
        \Modules\Tenant\ValueObjects\DynamicTenantConfig $template,
        array $applicationParams = [],
        ?Tenant $parent = null,
    ): Tenant {
        // Apply application parameters to the template by merging with existing data
        $templateArray = $template->toArray();
        $mergedConfig  = array_merge($templateArray['config'], $applicationParams);

        // Create a new template with the merged config
        $finalTemplate = new \Modules\Tenant\ValueObjects\DynamicTenantConfig(
            $mergedConfig,
            $templateArray['visibility'],
        );

        return $this->createTenant($domain, $name, $finalTemplate->toArray(), $parent);
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
        setTenantContext($tenant->id);

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
        setTenantContext($currentTenant->id);
    }
}
