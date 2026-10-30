<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Game;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_returns_accurate_database_metrics(): void
    {
        /** @var User $admin */
        $admin = User::factory()->admin()->create();

        $category = Category::factory()->create();
        Tag::factory()->count(3)->create();

        // 3 published games with plays
        Game::factory()->count(3)->create([
            'category_id' => $category->id,
            'status' => 'published',
            'plays_count' => 10,
        ]);

        // 2 draft games
        Game::factory()->count(2)->draft()->create([
            'category_id' => $category->id,
        ]);

        // 1 archived game
        Game::factory()->archived()->create([
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertStatus(200);

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Dashboard')
            ->has('stats', fn (Assert $stats) => $stats
                ->where('total_games', 6)
                ->where('published_games', 3)
                ->where('draft_games', 2)
                ->where('archived_games', 1)
                ->where('total_categories', 1)
                ->where('total_tags', 3)
                ->where('total_plays', 30)
            )
            ->has('recentGames', 5)
            ->has('pendingReviewGames', 2)
        );
    }
}
