<?php

namespace Modules\Tenant\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Tenant\app\Models\Tenant;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $apiDomain = parse_url(
            config('app.url'),
        )['host'] ?? config('app.url');

        $frontendDomain = parse_url(
            config('quvel.frontend_url'),
        )['host'] ?? config('quvel.frontend_url');

        Tenant::factory()->create([
            'name'   => 'API Tenant',
            'domain' => $apiDomain,
        ]);

        Tenant::factory()->create([
            'name'   => 'Frontend Tenant',
            'domain' => $frontendDomain,
        ]);

        Tenant::factory(5)->create();
    }
}
