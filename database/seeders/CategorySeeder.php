<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Action',
                'slug' => 'action',
                'description' => 'Fast-paced action games packed with challenges, combat, and quick reflexes.',
                'icon' => 'zap',
                'thumbnail' => '/images/thumbnails/shadow-blade.svg',
                'status' => 'active',
                'sort_order' => 1,
            ],
            [
                'name' => 'Arcade',
                'slug' => 'arcade',
                'description' => 'Classic and modern arcade games designed for instant fun and high scores.',
                'icon' => 'gamepad-2',
                'thumbnail' => '/images/thumbnails/neon-pulse.svg',
                'status' => 'active',
                'sort_order' => 2,
            ],
            [
                'name' => 'Puzzle',
                'slug' => 'puzzle',
                'description' => 'Engaging brain teasers, spatial logic challenges, and match mechanics.',
                'icon' => 'puzzle',
                'thumbnail' => '/images/thumbnails/mind-mesh.svg',
                'status' => 'active',
                'sort_order' => 3,
            ],
            [
                'name' => 'Racing',
                'slug' => 'racing',
                'description' => 'High-velocity racing titles featuring tracks, drifts, and futuristic vehicles.',
                'icon' => 'gauge',
                'thumbnail' => '/images/thumbnails/galaxy-drift.svg',
                'status' => 'active',
                'sort_order' => 4,
            ],
            [
                'name' => 'Adventure',
                'slug' => 'adventure',
                'description' => 'Immersive story-driven exploration and quest games with retro aesthetics.',
                'icon' => 'compass',
                'thumbnail' => '/images/thumbnails/pixel-quest.svg',
                'status' => 'active',
                'sort_order' => 5,
            ],
            [
                'name' => 'Sports',
                'slug' => 'sports',
                'description' => 'Competitive sports simulations and casual pick-up athletic minigames.',
                'icon' => 'trophy',
                'thumbnail' => '/images/thumbnails/hyper-hoops.svg',
                'status' => 'active',
                'sort_order' => 6,
            ],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(['slug' => $cat['slug']], $cat);
        }
    }
}
