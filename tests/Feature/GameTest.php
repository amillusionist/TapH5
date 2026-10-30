<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GameTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_games_listing_page_loads_with_pagination(): void
    {
        $category = Category::factory()->create();
        Game::factory()->count(20)->create(['category_id' => $category->id, 'status' => 'published']);

        $response = $this->get('/games');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Games/Index')
            ->has('games.data', 18)
            ->has('categories')
            ->has('filters')
        );
    }

    public function test_games_can_be_filtered_by_search_term(): void
    {
        $category = Category::factory()->create();
        Game::factory()->create([
            'title' => 'Neon Pulse Odyssey',
            'category_id' => $category->id,
            'status' => 'published',
        ]);
        Game::factory()->create([
            'title' => 'Shadow Knight Chronicles',
            'category_id' => $category->id,
            'status' => 'published',
        ]);

        $response = $this->get('/games?search=Neon');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Games/Index')
            ->has('games.data', 1)
            ->where('games.data.0.title', 'Neon Pulse Odyssey')
        );
    }

    public function test_games_can_be_filtered_by_category(): void
    {
        $arcade = Category::factory()->create(['slug' => 'arcade']);
        $puzzle = Category::factory()->create(['slug' => 'puzzle']);

        Game::factory()->create([
            'title' => 'Arcade Game 1',
            'category_id' => $arcade->id,
            'status' => 'published',
        ]);
        Game::factory()->create([
            'title' => 'Puzzle Game 1',
            'category_id' => $puzzle->id,
            'status' => 'published',
        ]);

        $response = $this->get('/games?category=arcade');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Games/Index')
            ->has('games.data', 1)
            ->where('games.data.0.title', 'Arcade Game 1')
        );
    }

    public function test_individual_game_page_loads_and_increments_plays(): void
    {
        $category = Category::factory()->create();
        $game = Game::factory()->create([
            'title' => 'Super Neon',
            'slug' => 'super-neon',
            'category_id' => $category->id,
            'status' => 'published',
            'plays_count' => 5,
        ]);

        $response = $this->get('/games/super-neon');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Games/Show')
            ->has('game')
            ->where('game.title', 'Super Neon')
            ->has('relatedGames')
            ->has('meta')
        );

        $this->assertEquals(6, $game->fresh()->plays_count);
    }

    public function test_draft_or_archived_game_returns_404(): void
    {
        $category = Category::factory()->create();
        Game::factory()->draft()->create(['slug' => 'secret-game', 'category_id' => $category->id]);
        Game::factory()->archived()->create(['slug' => 'old-game', 'category_id' => $category->id]);

        $this->get('/games/secret-game')->assertStatus(404);
        $this->get('/games/old-game')->assertStatus(404);
        $this->get('/games/non-existent-game')->assertStatus(404);
    }
}
