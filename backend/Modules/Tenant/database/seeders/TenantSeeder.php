<?php

namespace Modules\Tenant\database\seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Models\Tenant;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $apiDomain = parse_url(
            config('app.url'),
        )['host'] ?? throw new \Exception('Could not determine API domain');

        $frontendDomain = parse_url(
            config('quvel.frontend_url'),
        )['host'] ?? throw new \Exception('Could not determine frontend domain');

        $mainTenant = Tenant::updateOrCreate(
            ['domain' => $apiDomain],
            ['name' => 'First Tenant - API', 'public_id' => Str::ulid()->toString()],
        );

        Tenant::updateOrCreate(
            ['domain' => $frontendDomain, 'parent_id' => $mainTenant->id],
            ['name' => 'First Tenant - Frontend', 'public_id' => Str::ulid()->toString()],
        );

        $secondTenant = Tenant::updateOrCreate(
            ['domain' => 'second-tenant.127.0.0.1.nip.io'],
            ['name' => 'Second Tenant - API', 'public_id' => Str::ulid()->toString()],
        );

        // Set the context for the TenantScopedModel requirements on User
        app(TenantContext::class)->set($secondTenant);

        // Create a user for the second tenant.
        User::updateOrCreate(
            ['email' => 'second-tenant@quvel.app'],
            [
                'name'              => 'Second Tenant User',
                'tenant_id'         => $secondTenant->id,
                'password'          => Hash::make(config('quvel.default_password')),
                'email_verified_at' => now(),
            ],
        );
    }
}
