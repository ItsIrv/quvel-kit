<?php

namespace Modules\Tenant\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Tenant\app\Models\Tenant;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Tenant\app\Models\Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        return [
            'name'   => $this->faker->company,
            'domain' => $this->faker->unique()->domainName,
        ];
    }
}
