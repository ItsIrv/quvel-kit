<?php

namespace Modules\Catalog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Catalog\Models\CatalogItem;

class CatalogItemFactory extends Factory
{
    protected $model = CatalogItem::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid,
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->paragraph,
            'is_public' => $this->faker->boolean(70),
            'metadata' => [
                'tags' => $this->faker->words(3),
                'rating' => $this->faker->randomFloat(1, 1, 5),
            ],
        ];
    }
}
