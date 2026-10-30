<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = [
            ['name' => 'Single Player', 'slug' => 'single-player'],
            ['name' => 'HTML5', 'slug' => 'html5'],
            ['name' => 'Mobile Friendly', 'slug' => 'mobile-friendly'],
            ['name' => 'Touch Screen', 'slug' => 'touch-screen'],
            ['name' => 'Keyboard Controls', 'slug' => 'keyboard-controls'],
            ['name' => 'Casual', 'slug' => 'casual'],
            ['name' => 'Sci-Fi', 'slug' => 'sci-fi'],
            ['name' => 'Retro', 'slug' => 'retro'],
            ['name' => 'Fast Paced', 'slug' => 'fast-paced'],
        ];

        foreach ($tags as $tag) {
            Tag::updateOrCreate(['slug' => $tag['slug']], $tag);
        }
    }
}
