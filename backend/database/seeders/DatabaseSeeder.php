<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Nwidart\Modules\Facades\Module;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $allModules = collect(Module::getByStatus('enabled'));

        $prioritized = $allModules->filter(fn ($m) => $m->get('seed_priority') !== null)
            ->sortBy(fn ($m) => $m->get('seed_priority'));

        $nonPrioritized = $allModules->filter(fn ($m) => $m->get('seed_priority') === null);

        foreach ($prioritized as $module) {
            $this->seedModule($module);
        }

        // Then seed UserSeeder
        echo "Seeding: UserSeeder\n";
        $this->call(UserSeeder::class);

        foreach ($nonPrioritized as $module) {
            $this->seedModule($module);
        }
    }

    protected function seedModule($module): void
    {
        $name      = $module->getName();
        $className = "Modules\\$name\\Database\\Seeders\\{$name}DatabaseSeeder";

        if (class_exists($className)) {
            echo "Seeding module: {$name}\n";

            $this->call($className);
        }
    }
}
