<?php

declare(strict_types=1);

namespace Quvel\Tenant\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Quvel\Tenant\Models\Tenant;

class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $name = $this->faker->company();

        return [
            'public_id' => Str::uuid()->toString(),
            'name' => $name,
            'identifier' => Str::slug($name) . '-' . $this->faker->randomNumber(4),
            'is_active' => true,
            'config' => [],
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withConfig(array $config): static
    {
        return $this->state(fn (array $attributes) => [
            'config' => $config,
        ]);
    }

    public function child(Tenant $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent->id,
        ]);
    }
}