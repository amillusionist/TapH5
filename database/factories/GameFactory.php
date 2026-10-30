<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Game;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Game>
 */
class GameFactory extends Factory
{
    protected $model = Game::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->words(2, true).' '.fake()->numberBetween(1, 999);

        return [
            'title' => ucwords($title),
            'slug' => Str::slug($title),
            'description' => fake()->paragraph(),
            'instructions' => 'Use arrow keys or touch screen controls to play.',
            'thumbnail' => '/images/thumbnails/neon-pulse.svg',
            'category_id' => Category::factory(),
            'game_type' => 'single_file',
            'entry_file' => 'index.html',
            'storage_path' => 'fixtures/games/neon-pulse',
            'status' => 'published',
            'is_featured' => false,
            'is_trending' => false,
            'is_popular' => false,
            'plays_count' => 0,
            'seo_title' => null,
            'seo_description' => null,
        ];
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    public function trending(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_trending' => true,
        ]);
    }

    public function popular(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_popular' => true,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'archived',
        ]);
    }
}
