<?php

namespace Modules\Catalog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Catalog\Models\CatalogItem;

class CatalogItemFactory extends Factory
{
    protected $model = CatalogItem::class;

    public function definition(): array
    {
        $prefixes = [
            'Wander', 'Urban', 'Coastal', 'Forest', 'Alpine', 'Scenic', 'Nomad', 'Rustic', 'Harbor', 'Trail',
        ];

        $nouns = [
            'Explorer', 'Retreat', 'Escape', 'Journey', 'Vista', 'Getaway', 'Path', 'View', 'Haven', 'Trek',
        ];

        $categories = [
            'travel', 'landscapes', 'cities', 'nature', 'adventure', 'photography', 'outdoors', 'experiences', 'moments', 'wellness',
        ];

        $nameWords = $this->faker->words(2);
        $name = collect($nameWords)
            ->map(fn ($w) => ucfirst($w))
            ->implode(' ');

        $prefix = $this->faker->randomElement($prefixes);
        $noun = $this->faker->randomElement($nouns);
        $category = $this->faker->randomElement($categories);

        $descriptions = [
            'The :prefix :noun helps you rediscover the beauty of :category.',
            'Capture breathtaking :category moments with the :prefix :noun.',
            'Explore the unknown. The :prefix :noun is your gateway to :category.',
            'Let the :prefix :noun guide your next :category adventure.',
            'The :prefix :noun brings scenic :category right to your fingertips.',
            'From peaceful views to thrilling escapes, the :prefix :noun captures it all.',
            'Find calm in the chaos with the :prefix :noun, built for :category lovers.',
            'Step into serenity. The :prefix :noun redefines :category experiences.',
            'Designed for explorers, the :prefix :noun unlocks unforgettable :category.',
            'Elevate your senses. The :prefix :noun delivers raw, stunning :category.',
            'Your journey to stunning :category begins with the :prefix :noun.',
            'The :prefix :noun was made for those who breathe in :category landscapes.',
            'Plan your next story with the :prefix :noun — a tribute to :category.',
            'The essence of :category, now curated by the :prefix :noun.',
        ];

        $rawDescription = $this->faker->randomElement($descriptions);

        $description = str_replace(
            [':prefix', ':noun', ':category'],
            [$prefix, $noun, $category],
            $rawDescription
        ).' '.$this->faker->sentence();

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
