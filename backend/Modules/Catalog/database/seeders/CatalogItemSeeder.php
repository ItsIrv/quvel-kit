<?php

namespace Modules\Catalog\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Catalog\Models\CatalogItem;
use Modules\Tenant\Contexts\TenantContext;

class CatalogItemSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::limit(2)->get();
        app(TenantContext::class)->set($tenant);

        CatalogItem::factory()->count(30)->create([
            'user_id' => $users->first()->id,
            'tenant_id' => $tenant->id,
        ]);

        CatalogItem::factory()->count(30)->create([
            'user_id' => $users->last()->id,
            'tenant_id' => $tenant->id,
        ]);
    }
}
