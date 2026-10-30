<?php

namespace Tests\Feature;

use App\Models\AnalyticsDailyAggregate;
use App\Models\AnalyticsEvent;
use App\Models\Category;
use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->regularUser = User::factory()->create();
    }

    public function test_visitor_can_post_game_viewed_event_and_receives_visitor_cookie(): void
    {
        $category = Category::factory()->create();
        $game = Game::factory()->create([
            'category_id' => $category->id,
            'status' => 'published',
        ]);

        $response = $this->postJson('/api/analytics/events', [
            'event_type' => 'game_viewed',
            'game_id' => $game->id,
            'device_type' => 'desktop',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'recorded',
        ]);
        $response->assertCookie('taph5_vid');

        $this->assertDatabaseHas('analytics_events', [
            'game_id' => $game->id,
            'event_type' => 'game_viewed',
            'device_type' => 'desktop',
        ]);
    }

    public function test_invalid_event_type_is_rejected_with_422(): void
    {
        $category = Category::factory()->create();
        $game = Game::factory()->create([
            'category_id' => $category->id,
            'status' => 'published',
        ]);

        $response = $this->postJson('/api/analytics/events', [
            'event_type' => 'unsupported_exploit_event',
            'game_id' => $game->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_event_for_unpublished_game_is_rejected(): void
    {
        $category = Category::factory()->create();
        $draftGame = Game::factory()->draft()->create([
            'category_id' => $category->id,
        ]);

        $response = $this->postJson('/api/analytics/events', [
            'event_type' => 'game_viewed',
            'game_id' => $draftGame->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_game_play_clicked_increments_game_plays_count_with_rate_limiting(): void
    {
        $category = Category::factory()->create();
        $game = Game::factory()->create([
            'category_id' => $category->id,
            'status' => 'published',
            'plays_count' => 10,
        ]);

        $visitorId = 'vid-player-123456';

        // First play click
        $response1 = $this->postJson('/api/analytics/events', [
            'event_type' => 'game_play_clicked',
            'game_id' => $game->id,
            'visitor_id' => $visitorId,
        ]);

        $response1->assertStatus(200);
        $this->assertEquals(11, $game->fresh()->plays_count);

        // Immediate subsequent click from same visitor within 30s window (suppresses spam/double counting)
        $response2 = $this->postJson('/api/analytics/events', [
            'event_type' => 'game_play_clicked',
            'game_id' => $game->id,
            'visitor_id' => $visitorId,
        ]);

        $response2->assertStatus(200);
        // Play count should remain 11 (idempotent duplicate suppression)
        $this->assertEquals(11, $game->fresh()->plays_count);
    }

    public function test_game_session_ping_and_ended_record_duration(): void
    {
        $category = Category::factory()->create();
        $game = Game::factory()->create([
            'category_id' => $category->id,
            'status' => 'published',
        ]);

        $this->postJson('/api/analytics/events', [
            'event_type' => 'game_session_ping',
            'game_id' => $game->id,
            'duration_seconds' => 15,
            'visitor_id' => 'vid-duration-test',
        ])->assertStatus(200);

        $this->postJson('/api/analytics/events', [
            'event_type' => 'game_session_ended',
            'game_id' => $game->id,
            'duration_seconds' => 30,
            'visitor_id' => 'vid-duration-test',
        ])->assertStatus(200);

        $this->assertDatabaseHas('analytics_events', [
            'game_id' => $game->id,
            'event_type' => 'game_session_ping',
            'duration_seconds' => 15,
        ]);

        $this->assertDatabaseHas('analytics_events', [
            'game_id' => $game->id,
            'event_type' => 'game_session_ended',
            'duration_seconds' => 30,
        ]);
    }

    public function test_admin_analytics_requires_admin_role(): void
    {
        // Unauthenticated visitor is redirected to login
        $this->get('/admin/analytics')->assertRedirect('/login');

        // Regular non-admin user is rejected with 403
        $this->actingAs($this->regularUser)->get('/admin/analytics')->assertStatus(403);

        // Admin user can view analytics dashboard
        $response = $this->actingAs($this->admin)->get('/admin/analytics');
        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Analytics/Index')
            ->has('analytics.overview')
            ->has('analytics.trend')
            ->has('analytics.top_games')
            ->has('analytics.category_breakdown')
            ->has('analytics.device_breakdown')
        );
    }

    public function test_admin_analytics_computes_accurate_kpis_and_conversion(): void
    {
        $arcade = Category::factory()->create(['name' => 'Arcade', 'slug' => 'arcade']);
        $game = Game::factory()->create([
            'category_id' => $arcade->id,
            'title' => 'Neon Drift',
            'status' => 'published',
        ]);

        // 4 views
        for ($i = 0; $i < 4; $i++) {
            AnalyticsEvent::create([
                'game_id' => $game->id,
                'event_type' => 'game_viewed',
                'visitor_id' => "v-{$i}",
                'created_at' => now(),
            ]);
        }

        // 2 play clicks
        AnalyticsEvent::create([
            'game_id' => $game->id,
            'event_type' => 'game_play_clicked',
            'visitor_id' => 'v-0',
            'duration_seconds' => 60,
            'created_at' => now(),
        ]);
        AnalyticsEvent::create([
            'game_id' => $game->id,
            'event_type' => 'game_play_clicked',
            'visitor_id' => 'v-1',
            'duration_seconds' => 120,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/analytics?range=today');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Analytics/Index')
            ->where('analytics.overview.total_views', 4)
            ->where('analytics.overview.total_play_clicks', 2)
            ->where('analytics.overview.conversion_rate', fn ($val) => (float) $val === 50.0)
            ->where('analytics.overview.unique_players', 2)
            ->where('analytics.overview.total_engagement_seconds', 180)
            ->where('analytics.overview.avg_duration_seconds', 90)
        );
    }

    public function test_analytics_prune_command_aggregates_and_deletes_old_events(): void
    {
        $category = Category::factory()->create();
        $game = Game::factory()->create([
            'category_id' => $category->id,
            'status' => 'published',
        ]);

        $oldDate = Carbon::now()->subDays(100)->startOfDay();

        // Old historical events (100 days ago)
        AnalyticsEvent::create([
            'game_id' => $game->id,
            'event_type' => 'game_viewed',
            'visitor_id' => 'old-visitor-1',
            'created_at' => $oldDate->copy()->addHours(2),
        ]);

        AnalyticsEvent::create([
            'game_id' => $game->id,
            'event_type' => 'game_play_clicked',
            'visitor_id' => 'old-visitor-1',
            'duration_seconds' => 150,
            'created_at' => $oldDate->copy()->addHours(3),
        ]);

        // Recent event (today)
        AnalyticsEvent::create([
            'game_id' => $game->id,
            'event_type' => 'game_viewed',
            'visitor_id' => 'today-visitor',
            'created_at' => now(),
        ]);

        $this->assertEquals(3, AnalyticsEvent::count());

        // Run retention prune
        $this->artisan('analytics:prune', ['--days' => 90])
            ->expectsOutputToContain('Successfully aggregated 1 daily date-game groupings.')
            ->expectsOutputToContain('Pruned 2 historical raw events.')
            ->assertSuccessful();

        // 2 old events were pruned, 1 recent event remains
        $this->assertEquals(1, AnalyticsEvent::count());

        // Daily aggregate contains the rolled-up stats for the 100-day-old date
        $aggregate = AnalyticsDailyAggregate::where('game_id', $game->id)->first();
        $this->assertNotNull($aggregate);
        $this->assertEquals($oldDate->toDateString(), $aggregate->date->toDateString());
        $this->assertEquals(1, $aggregate->views_count);
        $this->assertEquals(1, $aggregate->play_clicks_count);
        $this->assertEquals(150, $aggregate->total_engagement_seconds);
    }
}
