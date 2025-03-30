<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Tenant\Database\Seeders\TenantDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(TenantDatabaseSeeder::class);
        $this->call(UserSeeder::class);
    }
}
