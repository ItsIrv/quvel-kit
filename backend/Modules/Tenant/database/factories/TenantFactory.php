<?php

namespace Modules\Tenant\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Tenant\Models\Tenant;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company,
            'domain' => $this->faker->unique()->domainName,
            'public_id' => Str::ulid()->toString(),
        ];
    }
}
