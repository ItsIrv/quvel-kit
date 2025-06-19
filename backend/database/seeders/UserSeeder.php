<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Main user
        $quvelUserData = User::factory()->make([
            'name'  => 'Quvel User',
            'email' => 'quvel@quvel.app',
        ])->toArray();

        $quvelUserData['password'] = Hash::make(
            config('quvel.default_password'),
        );

        User::updateOrCreate(
            ['email' => 'quvel@quvel.app'],
            $quvelUserData,
        );
    }
}
