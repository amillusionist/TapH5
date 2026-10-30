<?php

namespace Tests\Feature;

use App\Http\Controllers\HomeController;
use App\Models\Category;
use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PerformanceAndAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_caches_data_and_invalidates_on_game_save_or_delete(): void
    {
        $category = Category::factory()->create(['status' => 'active']);
        $game = Game::factory()->create([
            'category_id' => $category->id,
            'status' => 'published',
            'is_featured' => true,
        ]);

        Cache::forget(HomeController::CACHE_KEY);
        $this->assertFalse(Cache::has(HomeController::CACHE_KEY));

        // First request populates cache
        $this->get('/')->assertStatus(200);
        $this->assertTrue(Cache::has(HomeController::CACHE_KEY));

        // Modifying game clears cache automatically
        $game->update(['title' => 'Updated Game Title']);
        $this->assertFalse(Cache::has(HomeController::CACHE_KEY));

        // Populate cache again
        $this->get('/')->assertStatus(200);
        $this->assertTrue(Cache::has(HomeController::CACHE_KEY));

        // Deleting game clears cache automatically
        $game->delete();
        $this->assertFalse(Cache::has(HomeController::CACHE_KEY));
    }

    public function test_homepage_cache_invalidates_on_category_save_or_delete(): void
    {
        $category = Category::factory()->create(['status' => 'active']);
        Game::factory()->create([
            'category_id' => $category->id,
            'status' => 'published',
        ]);

        $this->get('/')->assertStatus(200);
        $this->assertTrue(Cache::has(HomeController::CACHE_KEY));

        // Modifying category clears cache automatically
        $category->update(['name' => 'Renamed Category']);
        $this->assertFalse(Cache::has(HomeController::CACHE_KEY));

        // Re-populate and delete
        $this->get('/')->assertStatus(200);
        $this->assertTrue(Cache::has(HomeController::CACHE_KEY));

        $category->delete();
        $this->assertFalse(Cache::has(HomeController::CACHE_KEY));
    }

    public function test_admin_dashboard_stats_aggregation_is_accurate(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();

        Game::factory()->create(['category_id' => $category->id, 'status' => 'published', 'plays_count' => 10]);
        Game::factory()->create(['category_id' => $category->id, 'status' => 'published', 'plays_count' => 20]);
        Game::factory()->create(['category_id' => $category->id, 'status' => 'draft', 'plays_count' => 0]);
        Game::factory()->create(['category_id' => $category->id, 'status' => 'archived', 'plays_count' => 5]);

        $response = $this->actingAs($admin)->get('/admin');
        $response->assertStatus(200);

        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Dashboard')
            ->where('stats.total_games', 4)
            ->where('stats.published_games', 2)
            ->where('stats.draft_games', 1)
            ->where('stats.archived_games', 1)
            ->where('stats.total_plays', 35)
        );
    }

    public function test_games_table_has_status_category_composite_index(): void
    {
        $this->assertTrue(Schema::hasTable('games'));

        // SQLite indexes can be inspected via PRAGMA index_list('games')
        $indexes = collect(DB::select("PRAGMA index_list('games')"))
            ->pluck('name')
            ->all();

        $hasComposite = collect($indexes)->contains(fn ($idx) => str_contains($idx, 'status_category_id'));
        $this->assertTrue($hasComposite, 'Expected games table to contain composite index for status and category_id');
    }
}
