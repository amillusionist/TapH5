<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameAutocompleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_autocomplete_returns_empty_for_queries_shorter_than_two_chars(): void
    {
        $response = $this->getJson(route('api.games.autocomplete', ['q' => 'a']));

        $response->assertOk()
            ->assertJson([
                'query' => 'a',
                'total' => 0,
                'results' => [],
            ]);
    }

    public function test_autocomplete_returns_matching_published_games(): void
    {
        $category = Category::factory()->create(['name' => 'Retro Arcade']);

        $game = Game::factory()->create([
            'title' => 'Retro Snake Adventure',
            'slug' => 'retro-snake-adventure',
            'category_id' => $category->id,
            'status' => 'published',
            'thumbnail' => '/images/thumbnails/retro-snake.svg',
            'game_type' => 'single_file',
        ]);

        $response = $this->getJson(route('api.games.autocomplete', ['q' => 'snake']));

        $response->assertOk()
            ->assertJson([
                'query' => 'snake',
                'total' => 1,
            ])
            ->assertJsonFragment([
                'id' => $game->id,
                'title' => 'Retro Snake Adventure',
                'slug' => 'retro-snake-adventure',
                'category_name' => 'Retro Arcade',
                'game_type' => 'single_file',
            ]);
    }

    public function test_autocomplete_does_not_return_draft_or_archived_games(): void
    {
        $category = Category::factory()->create();

        Game::factory()->create([
            'title' => 'Hidden Draft Snake',
            'slug' => 'hidden-draft-snake',
            'category_id' => $category->id,
            'status' => 'draft',
        ]);

        Game::factory()->create([
            'title' => 'Archived Snake Legacy',
            'slug' => 'archived-snake-legacy',
            'category_id' => $category->id,
            'status' => 'archived',
        ]);

        $response = $this->getJson(route('api.games.autocomplete', ['q' => 'Snake']));

        $response->assertOk()
            ->assertJson([
                'total' => 0,
                'results' => [],
            ]);
    }

    public function test_autocomplete_matches_category_name(): void
    {
        $category = Category::factory()->create(['name' => 'Puzzle Mania']);

        $game = Game::factory()->create([
            'title' => 'Mind Bender',
            'category_id' => $category->id,
            'status' => 'published',
        ]);

        $response = $this->getJson(route('api.games.autocomplete', ['q' => 'Puzzle']));

        $response->assertOk()
            ->assertJsonFragment([
                'id' => $game->id,
                'title' => 'Mind Bender',
            ]);
    }

    public function test_autocomplete_limits_results_to_eight(): void
    {
        $category = Category::factory()->create();

        for ($i = 1; $i <= 12; $i++) {
            Game::factory()->create([
                'title' => "Galaxy Fighter Part {$i}",
                'slug' => "galaxy-fighter-{$i}",
                'category_id' => $category->id,
                'status' => 'published',
            ]);
        }

        $response = $this->getJson(route('api.games.autocomplete', ['q' => 'Galaxy']));

        $response->assertOk();
        $this->assertCount(8, $response->json('results'));
    }

    public function test_autocomplete_does_not_expose_private_fields(): void
    {
        $category = Category::factory()->create();

        Game::factory()->create([
            'title' => 'Super Secret Runner',
            'slug' => 'super-secret-runner',
            'category_id' => $category->id,
            'status' => 'published',
            'storage_path' => 'private/system/fixtures/path',
            'entry_file' => 'index.html',
            'fingerprint' => 'sha256-super-secret-fingerprint',
            'scan_metadata' => ['internal_scanner' => 'v1.2', 'audit_pass' => true],
        ]);

        $response = $this->getJson(route('api.games.autocomplete', ['q' => 'Secret']));

        $response->assertOk();
        $item = $response->json('results.0');

        $this->assertArrayNotHasKey('storage_path', $item);
        $this->assertArrayNotHasKey('entry_file', $item);
        $this->assertArrayNotHasKey('fingerprint', $item);
        $this->assertArrayNotHasKey('scan_metadata', $item);
    }

    public function test_autocomplete_database_query_executes_rapidly(): void
    {
        $category = Category::factory()->create(['name' => 'Speed Trials']);

        Game::factory()->count(20)->create([
            'category_id' => $category->id,
            'status' => 'published',
        ]);

        $start = microtime(true);
        $response = $this->getJson(route('api.games.autocomplete', ['q' => 'Speed']));
        $durationMs = (microtime(true) - $start) * 1000;

        $response->assertOk();
        $this->assertLessThan(100.0, $durationMs, 'Autocomplete API should respond in under 100ms within PHP execution');
    }
}
