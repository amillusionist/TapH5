<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Action',
            'Arcade',
            'Puzzle',
            'Racing',
            'Adventure',
            'Sports',
            'Shooter',
            'Strategy',
        ]).' '.fake()->numberBetween(1, 999);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(12),
            'icon' => 'gamepad-2',
            'thumbnail' => null,
            'status' => 'active',
            'sort_order' => 0,
        ];
    }
}
