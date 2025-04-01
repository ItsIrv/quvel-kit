<?php

namespace Modules\Catalog\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Catalog\Models\CatalogItem;
use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Models\Tenant;

class CatalogItemSeeder extends Seeder
{
    public function run(): void
    {
        // Fetch two top-level tenants
        $tenants = Tenant::whereNull('parent_id')->limit(2)->get();

        foreach ($tenants as $tenant) {
            $user = User::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->inRandomOrder()
                ->first();

            if (!$user) {
                continue;
            }

            app(TenantContext::class)->set($tenant);

            CatalogItem::factory()
                ->count(100)
                ->create([
                    'user_id' => $user->id,
                ]);
        }
    }
}
