<?php

namespace Modules\Tenant\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Tenant\app\Models\Tenant;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        Tenant::factory()->create([
            'name'   => 'Default Tenant',
            'domain' => config('app.url'),
        ]);

        Tenant::factory()->create([
            'name'   => 'Frontend Tenant',
            'domain' => config('quvel.frontend_url'),
        ]);

        Tenant::factory(5)->create();
    }
}
