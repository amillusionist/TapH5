<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Game;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class GameSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $arcade = Category::where('slug', 'arcade')->first();
        $action = Category::where('slug', 'action')->first();
        $racing = Category::where('slug', 'racing')->first();
        $puzzle = Category::where('slug', 'puzzle')->first();
        $adventure = Category::where('slug', 'adventure')->first();
        $sports = Category::where('slug', 'sports')->first();

        $allTags = Tag::all();

        $games = [
            [
                'title' => 'Neon Pulse',
                'slug' => 'neon-pulse',
                'description' => 'A vibrant neon rhythm particle game where every touch creates rhythmic visual ripples. Play directly in your browser with zero downloads.',
                'instructions' => 'Click or tap anywhere on the game canvas to trigger energetic pulses and score points. Supports mouse clicks and multi-touch.',
                'thumbnail' => '/images/thumbnails/neon-pulse.svg',
                'category_id' => $arcade?->id ?? 1,
                'game_type' => 'single_file',
                'entry_file' => 'index.html',
                'storage_path' => 'fixtures/games/neon-pulse',
                'status' => 'published',
                'is_featured' => true,
                'is_trending' => true,
                'is_popular' => true,
                'plays_count' => 0,
                'seo_title' => 'Neon Pulse - Play Free Online HTML5 Game | TapH5',
                'seo_description' => 'Play Neon Pulse for free online on TapH5 Games. Fast, responsive HTML5 arcade gameplay on desktop and mobile.',
                'tags' => ['html5', 'arcade', 'touch-screen', 'fast-paced', 'single-player'],
            ],
            [
                'title' => 'Cyber Runner',
                'slug' => 'cyber-runner',
                'description' => 'Sprint across futuristic cyberpunk rooftops avoiding neon laser barriers and collecting data chips.',
                'instructions' => 'Press Spacebar or Up Arrow to jump. Down Arrow to slide. On mobile, swipe up to jump and swipe down to slide.',
                'thumbnail' => '/images/thumbnails/cyber-runner.svg',
                'category_id' => $action?->id ?? 1,
                'game_type' => 'multi_file',
                'entry_file' => 'index.html',
                'storage_path' => 'fixtures/games/neon-pulse',
                'status' => 'published',
                'is_featured' => true,
                'is_trending' => true,
                'is_popular' => false,
                'plays_count' => 0,
                'seo_title' => 'Cyber Runner - Futuristic Action Game | TapH5',
                'seo_description' => 'Dash through cyberpunk cityscapes in Cyber Runner on TapH5 Games. No download required.',
                'tags' => ['html5', 'sci-fi', 'fast-paced', 'single-player'],
            ],
            [
                'title' => 'Galaxy Drift',
                'slug' => 'galaxy-drift',
                'description' => 'Zero-gravity interplanetary racing with high speed drifting around orbital curves.',
                'instructions' => 'Use Left/Right arrow keys or touch the steer buttons on screen to control your drift trajectory.',
                'thumbnail' => '/images/thumbnails/galaxy-drift.svg',
                'category_id' => $racing?->id ?? 1,
                'game_type' => 'multi_file',
                'entry_file' => 'index.html',
                'storage_path' => 'fixtures/games/neon-pulse',
                'status' => 'published',
                'is_featured' => false,
                'is_trending' => true,
                'is_popular' => true,
                'seo_title' => 'Galaxy Drift - Space Racing Game | TapH5',
                'seo_description' => 'Race at hyperspeed in Galaxy Drift on TapH5. Play free directly in modern browsers.',
                'tags' => ['html5', 'racing', 'sci-fi'],
            ],
            [
                'title' => 'Mind Mesh',
                'slug' => 'mind-mesh',
                'description' => 'A clean and satisfying geometric puzzle game. Connect glowing nodes across an ever-shifting hexagonal web.',
                'instructions' => 'Click or drag between nodes to form closed geometric loops and clear each level.',
                'thumbnail' => '/images/thumbnails/mind-mesh.svg',
                'category_id' => $puzzle?->id ?? 1,
                'game_type' => 'single_file',
                'entry_file' => 'index.html',
                'storage_path' => 'fixtures/games/neon-pulse',
                'status' => 'published',
                'is_featured' => true,
                'is_trending' => false,
                'is_popular' => true,
                'seo_title' => 'Mind Mesh - Relaxing Geometric Logic Puzzle | TapH5',
                'seo_description' => 'Exercise your mind with Mind Mesh. Solve hexagonal logic puzzles online at TapH5 Games.',
                'tags' => ['html5', 'casual', 'single-player'],
            ],
            [
                'title' => 'Pixel Quest',
                'slug' => 'pixel-quest',
                'description' => 'A retro-inspired fantasy dungeon quest filled with mystery, monsters, and treasures.',
                'instructions' => 'WASD or Arrow keys to move, Z or Space to interact and attack. Touch virtual D-pad on mobile.',
                'thumbnail' => '/images/thumbnails/pixel-quest.svg',
                'category_id' => $adventure?->id ?? 1,
                'game_type' => 'multi_file',
                'entry_file' => 'index.html',
                'storage_path' => 'fixtures/games/neon-pulse',
                'status' => 'published',
                'is_featured' => false,
                'is_trending' => false,
                'is_popular' => false,
                'seo_title' => 'Pixel Quest - Retro Dungeon Adventure | TapH5',
                'seo_description' => 'Explore retro dungeons and defeat mythical beasts in Pixel Quest on TapH5 Games.',
                'tags' => ['html5', 'retro', 'single-player'],
            ],
            [
                'title' => 'Shadow Blade',
                'slug' => 'shadow-blade',
                'description' => 'Test your samurai reaction speed. Strike precision targets as they appear from the shadows.',
                'instructions' => 'Click or swipe precisely across targets before they vanish. Timing is everything.',
                'thumbnail' => '/images/thumbnails/shadow-blade.svg',
                'category_id' => $action?->id ?? 1,
                'game_type' => 'single_file',
                'entry_file' => 'index.html',
                'storage_path' => 'fixtures/games/neon-pulse',
                'status' => 'published',
                'is_featured' => false,
                'is_trending' => true,
                'is_popular' => false,
                'seo_title' => 'Shadow Blade - Ninja Reaction Combat | TapH5',
                'seo_description' => 'Sharpen your reflexes with Shadow Blade. Play the fast-paced ninja action game on TapH5.',
                'tags' => ['html5', 'fast-paced', 'touch-screen'],
            ],
            [
                'title' => 'Astro Blaster',
                'slug' => 'astro-blaster',
                'description' => 'Defend orbital space stations against descending waves of rogue asteroids and alien ships.',
                'instructions' => 'Move mouse or drag finger to aim and steer ship. Auto-fires continuously.',
                'thumbnail' => '/images/thumbnails/astro-blaster.svg',
                'category_id' => $arcade?->id ?? 1,
                'game_type' => 'single_file',
                'entry_file' => 'index.html',
                'storage_path' => 'fixtures/games/neon-pulse',
                'status' => 'published',
                'is_featured' => false,
                'is_trending' => false,
                'is_popular' => true,
                'seo_title' => 'Astro Blaster - Classic Space Shooter | TapH5',
                'seo_description' => 'Blast asteroids and alien fleets in Astro Blaster online for free on TapH5 Games.',
                'tags' => ['html5', 'sci-fi', 'arcade'],
            ],
            [
                'title' => 'Hyper Hoops',
                'slug' => 'hyper-hoops',
                'description' => 'Arcade basketball shootout with realistic physics, combo bonuses, and sudden-death buzzer beaters.',
                'instructions' => 'Drag back and release to set ball arc and power towards the hoop.',
                'thumbnail' => '/images/thumbnails/hyper-hoops.svg',
                'category_id' => $sports?->id ?? 1,
                'game_type' => 'single_file',
                'entry_file' => 'index.html',
                'storage_path' => 'fixtures/games/neon-pulse',
                'status' => 'published',
                'is_featured' => false,
                'is_trending' => false,
                'is_popular' => false,
                'seo_title' => 'Hyper Hoops - Arcade Basketball Shootout | TapH5',
                'seo_description' => 'Sink buzzer beaters and chain high-scoring basketball shots in Hyper Hoops on TapH5.',
                'tags' => ['html5', 'casual', 'touch-screen'],
            ],
        ];

        foreach ($games as $data) {
            $tagSlugs = $data['tags'] ?? [];
            unset($data['tags']);

            $game = Game::updateOrCreate(['slug' => $data['slug']], $data);

            if (! empty($tagSlugs)) {
                $matchedTags = $allTags->whereIn('slug', $tagSlugs)->pluck('id');
                $game->tags()->sync($matchedTags);
            }
        }
    }
}
