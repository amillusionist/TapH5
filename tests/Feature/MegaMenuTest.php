<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class MegaMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_mega_menu_api_returns_published_popular_games(): void
    {
        Cache::forget('taph5_mega_menu_games');

        $category = Category::factory()->create(['name' => 'Arcade']);

        $publishedGame = Game::factory()->create([
            'title' => 'Galactic Invaders',
            'slug' => 'galactic-invaders',
            'category_id' => $category->id,
            'status' => 'published',
            'is_popular' => true,
            'plays_count' => 1250,
        ]);

        $draftGame = Game::factory()->create([
            'title' => 'Secret Prototype',
            'slug' => 'secret-prototype',
            'status' => 'draft',
        ]);

        $response = $this->getJson(route('api.games.mega_menu'));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'slug',
                        'thumbnail',
                        'category',
                        'plays_count',
                    ],
                ],
            ]);

        $titles = collect($response->json('data'))->pluck('title')->all();
        $this->assertContains('Galactic Invaders', $titles);
        $this->assertNotContains('Secret Prototype', $titles);
    }

    public function test_mega_menu_games_are_shared_via_inertia(): void
    {
        Cache::forget('taph5_mega_menu_games');

        $category = Category::factory()->create(['name' => 'Puzzle']);

        Game::factory()->create([
            'title' => 'Block Master 3D',
            'slug' => 'block-master-3d',
            'category_id' => $category->id,
            'status' => 'published',
            'is_popular' => true,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('megaMenuGames')
            ->where('megaMenuGames.0.title', 'Block Master 3D')
        );
    }
}
