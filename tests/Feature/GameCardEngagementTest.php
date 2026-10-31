<?php

namespace Tests\Feature;

use App\Http\Resources\GameResource;
use App\Models\AnalyticsEvent;
use App\Models\Category;
use App\Models\Game;
use App\Services\AnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class GameCardEngagementTest extends TestCase
{
    use RefreshDatabase;

    protected Category $category;

    protected Game $gameA;

    protected Game $gameB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create([
            'name' => 'Arcade',
            'slug' => 'arcade',
            'status' => 'active',
            'sort_order' => 1,
        ]);

        $this->gameA = Game::create([
            'title' => 'Alpha Asteroids',
            'slug' => 'alpha-asteroids',
            'description' => 'Test description',
            'category_id' => $this->category->id,
            'game_type' => 'single_file',
            'entry_file' => 'index.html',
            'status' => 'published',
            'plays_count' => 1250,
        ]);

        $this->gameB = Game::create([
            'title' => 'Beta Blaster',
            'slug' => 'beta-blaster',
            'description' => 'Second test description',
            'category_id' => $this->category->id,
            'game_type' => 'single_file',
            'entry_file' => 'index.html',
            'status' => 'published',
            'plays_count' => 0,
        ]);

        Cache::flush();
    }

    public function test_game_resource_exposes_engagement_and_rating_fields(): void
    {
        $resource = (new GameResource($this->gameA))->resolve();

        $this->assertArrayHasKey('plays_count', $resource);
        $this->assertEquals(1250, $resource['plays_count']);

        $this->assertArrayHasKey('rating', $resource);
        $this->assertNull($resource['rating']);

        $this->assertArrayHasKey('rating_count', $resource);
        $this->assertEquals(0, $resource['rating_count']);

        $this->assertArrayHasKey('playing_now', $resource);
        $this->assertEquals(0, $resource['playing_now']);
    }

    public function test_active_players_count_identifies_sessions_with_recent_heartbeats(): void
    {
        $analyticsService = app(AnalyticsService::class);

        // Session 1: recent ping on Game A (30s ago)
        AnalyticsEvent::create([
            'game_id' => $this->gameA->id,
            'event_type' => 'game_session_ping',
            'visitor_id' => 'v-1',
            'session_id' => 's-1',
            'created_at' => Carbon::now()->subSeconds(30),
        ]);

        // Session 2: recent load on Game A (10s ago)
        AnalyticsEvent::create([
            'game_id' => $this->gameA->id,
            'event_type' => 'game_loaded',
            'visitor_id' => 'v-2',
            'session_id' => 's-2',
            'created_at' => Carbon::now()->subSeconds(10),
        ]);

        $counts = $analyticsService->getActivePlayersCounts();

        $this->assertEquals(2, $counts[$this->gameA->id] ?? 0);
        $this->assertEquals(0, $counts[$this->gameB->id] ?? 0);
    }

    public function test_active_players_count_deduplicates_multiple_heartbeats_from_same_session(): void
    {
        $analyticsService = app(AnalyticsService::class);

        // Same session sends 3 pings within 90s
        for ($i = 0; $i < 3; $i++) {
            AnalyticsEvent::create([
                'game_id' => $this->gameA->id,
                'event_type' => 'game_session_ping',
                'visitor_id' => 'v-1',
                'session_id' => 's-duplicate',
                'created_at' => Carbon::now()->subSeconds(20 + ($i * 10)),
            ]);
        }

        $counts = $analyticsService->getActivePlayersCounts();

        $this->assertEquals(1, $counts[$this->gameA->id] ?? 0);
    }

    public function test_active_players_count_excludes_expired_sessions_older_than_90_seconds(): void
    {
        $analyticsService = app(AnalyticsService::class);

        // Old ping (120s ago - expired)
        AnalyticsEvent::create([
            'game_id' => $this->gameA->id,
            'event_type' => 'game_session_ping',
            'visitor_id' => 'v-old',
            'session_id' => 's-old',
            'created_at' => Carbon::now()->subSeconds(120),
        ]);

        $counts = $analyticsService->getActivePlayersCounts();

        $this->assertEquals(0, $counts[$this->gameA->id] ?? 0);
    }

    public function test_active_players_count_excludes_explicitly_ended_sessions(): void
    {
        $analyticsService = app(AnalyticsService::class);

        // Session started & pinged 40s ago
        AnalyticsEvent::create([
            'game_id' => $this->gameA->id,
            'event_type' => 'game_session_ping',
            'visitor_id' => 'v-ended',
            'session_id' => 's-ended',
            'created_at' => Carbon::now()->subSeconds(40),
        ]);

        // Same session ended 20s ago
        AnalyticsEvent::create([
            'game_id' => $this->gameA->id,
            'event_type' => 'game_session_ended',
            'visitor_id' => 'v-ended',
            'session_id' => 's-ended',
            'created_at' => Carbon::now()->subSeconds(20),
        ]);

        $counts = $analyticsService->getActivePlayersCounts();

        $this->assertEquals(0, $counts[$this->gameA->id] ?? 0);
    }

    public function test_batch_live_players_api_endpoint_returns_counts(): void
    {
        AnalyticsEvent::create([
            'game_id' => $this->gameA->id,
            'event_type' => 'game_session_ping',
            'visitor_id' => 'v-api',
            'session_id' => 's-api',
            'created_at' => Carbon::now()->subSeconds(15),
        ]);

        $response = $this->getJson(route('api.games.live_players'));

        $response->assertOk()
            ->assertJsonPath("live_players.{$this->gameA->id}", 1);
    }

    public function test_batch_live_players_api_endpoint_filters_by_ids(): void
    {
        AnalyticsEvent::create([
            'game_id' => $this->gameA->id,
            'event_type' => 'game_session_ping',
            'visitor_id' => 'v-1',
            'session_id' => 's-1',
            'created_at' => Carbon::now()->subSeconds(15),
        ]);

        AnalyticsEvent::create([
            'game_id' => $this->gameB->id,
            'event_type' => 'game_session_ping',
            'visitor_id' => 'v-2',
            'session_id' => 's-2',
            'created_at' => Carbon::now()->subSeconds(15),
        ]);

        $response = $this->getJson(route('api.games.live_players', ['ids' => (string) $this->gameA->id]));

        $response->assertOk()
            ->assertJsonPath("live_players.{$this->gameA->id}", 1)
            ->assertJsonMissingPath("live_players.{$this->gameB->id}");
    }
}
