<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Tenant\app\Contexts\TenantContext;
use Modules\Tenant\app\Models\Tenant;

class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $tenant = Tenant::first();

        app(TenantContext::class)->set($tenant);

        $quvelUserData = User::factory()->make([
            'email'     => 'quvel@quvel.app',
            'tenant_id' => $tenant->id,
        ])->toArray();

        $quvelUserData['password'] = Hash::make('12345678');

        User::updateOrCreate(
            ['email' => 'quvel@quvel.app'],
            $quvelUserData,
        );

        $currentUserCount = User::where('tenant_id', $tenant->id)->count();

        if ($currentUserCount < 10) {
            User::factory(10 - $currentUserCount)->create([
                'tenant_id' => $tenant->id,
            ]);
        }
    }
}
