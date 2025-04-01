<?php

namespace Modules\Catalog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Catalog\Models\CatalogItem;

class CatalogItemFactory extends Factory
{
    protected $model = CatalogItem::class;

    public function definition(): array
    {
        $prefixes = ['Smart', 'Quick', 'Eco', 'Easy', 'Flex', 'Sync', 'Auto', 'Pro', 'Go', 'Nova'];
        $nouns = ['Tracker', 'Planner', 'Manager', 'Assistant', 'Portal', 'Tool', 'App', 'Board', 'Flow', 'Suite'];
        $categories = ['finance', 'tasks', 'health', 'studies', 'projects', 'events', 'budgets', 'time', 'living', 'work'];

        // Generate two random words and title-case them
        $nameWords = $this->faker->words(2);
        $name = collect($nameWords)
            ->map(fn($w) => ucfirst($w))
            ->implode(' ');

        $prefix = $this->faker->randomElement($prefixes);
        $noun = $this->faker->randomElement($nouns);
        $category = $this->faker->randomElement($categories);

        $descriptions = [
            'The :prefix :noun is your all-in-one solution for managing :category.',
            'Easily stay on top of your :category with the :prefix :noun.',
            'Simplify your :category workflows using the :prefix :noun.',
            'The :prefix :noun helps you organize your :category like never before.',
            'Boost your productivity in :category using the :prefix :noun.',
            'Built for simplicity, the :prefix :noun transforms how you handle :category.',
            'From chaos to clarity — that’s what the :prefix :noun brings to your :category.',
            'Discover a smarter way to handle :category with the :prefix :noun.',
            'Say goodbye to hassle. The :prefix :noun is here for your :category needs.',
            'Your :category deserves the power of the :prefix :noun.',
        ];

        $rawDescription = $this->faker->randomElement($descriptions);

        $description = str_replace(
            [':prefix', ':noun', ':category'],
            [$prefix, $noun, $category],
            $rawDescription
        ) . ' ' . $this->faker->sentence();

        return [
            'uuid' => $this->faker->uuid,
            'name' => $name,
            'description' => $description,
            'image' => "https://picsum.photos/seed/{$this->faker->uuid}/600/400",
            'is_public' => $this->faker->boolean(70),
            'metadata' => [
                'tags' => $this->faker->words(3),
                'rating' => $this->faker->randomFloat(1, 1, 5),
            ],
        ];
    }
}
