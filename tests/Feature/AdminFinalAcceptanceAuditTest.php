<?php

namespace Tests\Feature;

use App\Models\AdminAuditLog;
use App\Models\AnalyticsEvent;
use App\Models\Category;
use App\Models\Game;
use App\Models\HomepageSection;
use App\Models\HomepageSlide;
use App\Models\Tag;
use App\Models\User;
use App\Services\Admin\AdminAuditService;
use App\Services\AnalyticsService;
use App\Services\HomepageContentService;
use App\Services\PlatformSettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AdminFinalAcceptanceAuditTest extends TestCase
{
    use RefreshDatabase;

    /* =========================================================================
     * SECTION 1: ROLE & PERMISSION AUDIT
     * ========================================================================= */

    public function test_guest_is_redirected_to_login_on_admin_routes(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->get('/admin/games')->assertRedirect('/login');
        $this->get('/admin/users')->assertRedirect('/login');
        $this->get('/admin/settings')->assertRedirect('/login');
        $this->get('/admin/system')->assertRedirect('/login');
    }

    public function test_standard_user_is_denied_access_to_all_admin_routes(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get('/admin')->assertStatus(403);
        $this->actingAs($user)->get('/admin/games')->assertStatus(403);
        $this->actingAs($user)->get('/admin/homepage')->assertStatus(403);
        $this->actingAs($user)->get('/admin/users')->assertStatus(403);
        $this->actingAs($user)->get('/admin/settings')->assertStatus(403);
        $this->actingAs($user)->get('/admin/system')->assertStatus(403);
    }

    public function test_analyst_has_read_only_access_and_cannot_mutate_content_or_access_system(): void
    {
        /** @var User $analyst */
        $analyst = User::factory()->analyst()->create();

        // Allowed: Dashboard, Analytics, Read-only catalog
        $this->actingAs($analyst)->get('/admin')->assertStatus(200);
        $this->actingAs($analyst)->get('/admin/analytics')->assertStatus(200);
        $this->actingAs($analyst)->get('/admin/games')->assertStatus(200);
        $this->actingAs($analyst)->get('/admin/categories')->assertStatus(200);
        $this->actingAs($analyst)->get('/admin/tags')->assertStatus(200);

        // Denied: Homepage management, Content mutations, Settings, Users, System
        $this->actingAs($analyst)->get('/admin/homepage')->assertStatus(403);
        $this->actingAs($analyst)->get('/admin/categories/create')->assertStatus(403);
        $this->actingAs($analyst)->post('/admin/categories', ['name' => 'Test'])->assertStatus(403);
        $this->actingAs($analyst)->post('/admin/games/bulk', ['action' => 'archive', 'game_ids' => [1]])->assertStatus(403);
        $this->actingAs($analyst)->get('/admin/monetization')->assertStatus(403);
        $this->actingAs($analyst)->get('/admin/settings')->assertStatus(403);
        $this->actingAs($analyst)->get('/admin/users')->assertStatus(403);
        $this->actingAs($analyst)->get('/admin/system')->assertStatus(403);
    }

    public function test_content_manager_can_manage_catalog_and_homepage_but_not_analytics_or_system(): void
    {
        /** @var User $cm */
        $cm = User::factory()->contentManager()->create();

        // Allowed: Dashboard, Homepage, Games mutation, Categories, Tags
        $this->actingAs($cm)->get('/admin')->assertStatus(200);
        $this->actingAs($cm)->get('/admin/homepage')->assertStatus(200);
        $this->actingAs($cm)->get('/admin/games')->assertStatus(200);
        $this->actingAs($cm)->get('/admin/categories/create')->assertStatus(200);
        $this->actingAs($cm)->get('/admin/tags/create')->assertStatus(200);

        // Denied: Analytics, Monetization, Users, Settings, System
        $this->actingAs($cm)->get('/admin/analytics')->assertStatus(403);
        $this->actingAs($cm)->get('/admin/monetization')->assertStatus(403);
        $this->actingAs($cm)->get('/admin/users')->assertStatus(403);
        $this->actingAs($cm)->get('/admin/settings')->assertStatus(403);
        $this->actingAs($cm)->get('/admin/system')->assertStatus(403);
    }

    public function test_admin_cannot_access_super_admin_exclusive_tools(): void
    {
        /** @var User $admin */
        $admin = User::factory()->admin()->create();

        // Allowed: Content, Analytics, Monetization
        $this->actingAs($admin)->get('/admin')->assertStatus(200);
        $this->actingAs($admin)->get('/admin/homepage')->assertStatus(200);
        $this->actingAs($admin)->get('/admin/analytics')->assertStatus(200);
        $this->actingAs($admin)->get('/admin/monetization')->assertStatus(200);

        // Denied: Users, Settings, System Operations
        $this->actingAs($admin)->get('/admin/users')->assertStatus(403);
        $this->actingAs($admin)->put('/admin/settings', ['group' => 'general'])->assertStatus(403);
        $this->actingAs($admin)->get('/admin/system')->assertStatus(403);
        $this->actingAs($admin)->post('/admin/system/maintenance/clear-cache', ['cache_type' => 'all'])->assertStatus(403);
    }

    public function test_super_admin_has_unrestricted_access(): void
    {
        /** @var User $superAdmin */
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)->get('/admin')->assertStatus(200);
        $this->actingAs($superAdmin)->get('/admin/homepage')->assertStatus(200);
        $this->actingAs($superAdmin)->get('/admin/games')->assertStatus(200);
        $this->actingAs($superAdmin)->get('/admin/analytics')->assertStatus(200);
        $this->actingAs($superAdmin)->get('/admin/monetization')->assertStatus(200);
        $this->actingAs($superAdmin)->get('/admin/users')->assertStatus(200);
        $this->actingAs($superAdmin)->get('/admin/settings')->assertStatus(200);
        $this->actingAs($superAdmin)->get('/admin/system')->assertStatus(200);
        $this->actingAs($superAdmin)->get('/admin/system/logs')->assertStatus(200);
    }

    public function test_role_escalation_and_last_super_admin_safeguards(): void
    {
        /** @var User $superAdmin */
        $superAdmin = User::factory()->superAdmin()->create();

        // Attempt to demote lone super admin
        $this->from('/admin/users')->actingAs($superAdmin)->put("/admin/users/{$superAdmin->id}/role", [
            'role' => 'admin',
        ])->assertSessionHas('error');
        $this->assertEquals('super_admin', $superAdmin->fresh()->role);

        // Attempt self-deactivation
        $this->from('/admin/users')->actingAs($superAdmin)->post("/admin/users/{$superAdmin->id}/toggle-status")
            ->assertSessionHas('error');
        $this->assertTrue($superAdmin->fresh()->is_active);

        // Create second super admin; now first super admin CAN be demoted
        /** @var User $secondSuperAdmin */
        $secondSuperAdmin = User::factory()->superAdmin()->create();
        $this->from('/admin/users')->actingAs($superAdmin)->put("/admin/users/{$secondSuperAdmin->id}/role", [
            'role' => 'admin',
        ])->assertSessionHas('success');
        $this->assertEquals('admin', $secondSuperAdmin->fresh()->role);
    }

    public function test_deactivated_account_is_blocked_immediately(): void
    {
        /** @var User $staffUser */
        $staffUser = User::factory()->admin()->create(['is_active' => false]);

        $this->actingAs($staffUser)->get('/admin')->assertStatus(403);
    }

    /* =========================================================================
     * SECTION 2: HOMEPAGE CONTENT INTEGRITY
     * ========================================================================= */

    public function test_disabled_sections_do_not_render_publicly(): void
    {
        $service = app(HomepageContentService::class);
        $section = HomepageSection::firstOrCreate(
            ['section_key' => 'trending'],
            ['title' => 'Trending Games', 'sort_order' => 2, 'items_limit' => 6, 'is_enabled' => true]
        );

        // Enable and verify
        $section->update(['is_enabled' => true]);
        $service->clearCache();
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->where('sections.trending.is_enabled', true));

        // Disable and verify public omission
        $section->update(['is_enabled' => false]);
        $service->clearCache();
        $responseDisabled = $this->get('/');
        $responseDisabled->assertStatus(200);
        $responseDisabled->assertInertia(fn ($page) => $page
            ->where('sections.trending.is_enabled', false)
            ->where('trendingGames', [])
        );
    }

    public function test_section_ordering_and_curated_game_ordering(): void
    {
        $cat = Category::factory()->create();
        $game1 = Game::factory()->create(['category_id' => $cat->id, 'title' => 'Game Alpha', 'status' => 'published']);
        $game2 = Game::factory()->create(['category_id' => $cat->id, 'title' => 'Game Beta', 'status' => 'published']);

        $section = HomepageSection::firstOrCreate(
            ['section_key' => 'featured'],
            ['title' => 'Featured Spotlight', 'sort_order' => 1, 'items_limit' => 6, 'is_enabled' => true]
        );

        // Curate specific game order: Beta then Alpha
        $section->update([
            'curated_game_ids' => [$game2->id, $game1->id],
        ]);
        app(HomepageContentService::class)->clearCache();

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('featuredGames.0.id', $game2->id)
            ->where('featuredGames.1.id', $game1->id)
        );
    }

    public function test_draft_and_archived_games_never_appear_publicly_even_if_curated(): void
    {
        $cat = Category::factory()->create();
        $draftGame = Game::factory()->create(['category_id' => $cat->id, 'status' => 'draft']);
        $archivedGame = Game::factory()->create(['category_id' => $cat->id, 'status' => 'archived']);
        $publishedGame = Game::factory()->create(['category_id' => $cat->id, 'status' => 'published']);

        $section = HomepageSection::firstOrCreate(
            ['section_key' => 'featured'],
            ['title' => 'Featured Spotlight', 'sort_order' => 1, 'items_limit' => 6, 'is_enabled' => true]
        );

        $section->update([
            'curated_game_ids' => [$draftGame->id, $archivedGame->id, $publishedGame->id],
        ]);
        app(HomepageContentService::class)->clearCache();

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('featuredGames', 1)
            ->where('featuredGames.0.id', $publishedGame->id)
        );
    }

    public function test_hero_slides_with_custom_content_are_handled_gracefully(): void
    {
        $slide = HomepageSlide::create([
            'game_id' => null, // Standalone promo slide without specific game relation
            'title' => 'Special Arcade Event',
            'subtitle' => 'Play free all weekend long',
            'cta_text' => 'Explore',
            'cta_url' => '/games',
            'is_enabled' => true,
            'sort_order' => 1,
        ]);
        app(HomepageContentService::class)->clearCache();

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('heroGames')
            ->has('sections')
        );
    }

    /* =========================================================================
     * SECTION 3: GAME CATALOG DELETION & BULK OPERATIONS
     * ========================================================================= */

    public function test_only_draft_or_archived_games_can_be_deleted(): void
    {
        /** @var User $superAdmin */
        $superAdmin = User::factory()->superAdmin()->create();
        $published = Game::factory()->create(['status' => 'published']);
        $draft = Game::factory()->create(['status' => 'draft']);
        $archived = Game::factory()->create(['status' => 'archived']);

        $this->actingAs($superAdmin)->post('/admin/games/bulk', [
            'action' => 'delete',
            'game_ids' => [$published->id, $draft->id, $archived->id],
        ]);

        // Published must NOT be deleted
        $this->assertDatabaseHas('games', ['id' => $published->id]);
        // Draft and Archived MUST be deleted
        $this->assertDatabaseMissing('games', ['id' => $draft->id]);
        $this->assertDatabaseMissing('games', ['id' => $archived->id]);
    }

    public function test_deleting_game_detaches_tag_relationships(): void
    {
        /** @var User $superAdmin */
        $superAdmin = User::factory()->superAdmin()->create();
        $tag = Tag::factory()->create(['name' => 'Retro']);
        $game = Game::factory()->create(['status' => 'draft']);
        $game->tags()->attach($tag->id);

        $this->actingAs($superAdmin)->post('/admin/games/bulk', [
            'action' => 'delete',
            'game_ids' => [$game->id],
        ]);

        $this->assertDatabaseMissing('games', ['id' => $game->id]);
        $this->assertDatabaseMissing('game_tag', ['game_id' => $game->id, 'tag_id' => $tag->id]);
        // Tag itself must still exist
        $this->assertDatabaseHas('tags', ['id' => $tag->id]);
    }

    /* =========================================================================
     * SECTION 4: PLATFORM SETTINGS INTEGRITY & RUNTIME OVERRIDES
     * ========================================================================= */

    public function test_platform_settings_override_defaults_with_type_conversion(): void
    {
        /** @var PlatformSettingService $settings */
        $settings = app(PlatformSettingService::class);

        // Initially default
        $this->assertEquals('TapH5 Games', $settings->get('site_name'));

        // Update database setting
        $settings->set('site_name', 'TapH5 Pro Arcade', 'general', 'string');
        $settings->set('default_games_per_page', 36, 'gameplay', 'integer');
        $settings->set('ads_enabled', true, 'monetization', 'boolean');

        $this->assertEquals('TapH5 Pro Arcade', $settings->get('site_name'));
        $this->assertSame(36, (int) $settings->get('default_games_per_page'));
        $this->assertTrue((bool) $settings->get('ads_enabled'));

        // Cache flush verification
        $this->assertTrue(Cache::has(PlatformSettingService::CACHE_KEY));
        $settings->clearCache();
        $this->assertFalse(Cache::has(PlatformSettingService::CACHE_KEY));
    }

    public function test_settings_validation_rejects_invalid_values(): void
    {
        /** @var User $superAdmin */
        $superAdmin = User::factory()->superAdmin()->create();

        // Invalid email in general settings
        $response = $this->actingAs($superAdmin)->put('/admin/settings', [
            'group' => 'general',
            'site_name' => 'TapH5',
            'site_description' => 'Description',
            'contact_email' => 'invalid-not-an-email',
            'default_language' => 'en',
            'default_timezone' => 'UTC',
        ]);

        $response->assertSessionHasErrors('contact_email');
    }

    /* =========================================================================
     * SECTION 5: SYSTEM MAINTENANCE & DIAGNOSTICS
     * ========================================================================= */

    public function test_system_maintenance_allowlisted_cache_clearing(): void
    {
        /** @var User $superAdmin */
        $superAdmin = User::factory()->superAdmin()->create();

        foreach (['app', 'config', 'route', 'view', 'all'] as $type) {
            $response = $this->from('/admin/system')->actingAs($superAdmin)->post('/admin/system/maintenance/clear-cache', [
                'cache_type' => $type,
            ]);
            $response->assertRedirect('/admin/system');
            $response->assertSessionHas('success');
        }
    }

    public function test_system_health_diagnostics_returns_real_runtime_data(): void
    {
        /** @var User $superAdmin */
        $superAdmin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($superAdmin)->get('/admin/system');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('system.php_version')
            ->has('system.laravel_version')
            ->has('system.db_status')
            ->where('system.db_status', 'healthy')
            ->has('system.storage.is_writable')
        );
    }

    /* =========================================================================
     * SECTION 6: AUDIT LOG & PRIVACY
     * ========================================================================= */

    public function test_audit_log_records_attribution_and_sanitizes_sensitive_keys(): void
    {
        /** @var User $superAdmin */
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)->put('/admin/settings', [
            'group' => 'general',
            'site_name' => 'Audited Portal',
            'site_description' => 'Audited Description',
            'contact_email' => 'admin@audited.com',
            'default_language' => 'en',
            'default_timezone' => 'UTC',
        ]);

        $log = AdminAuditLog::where('action', 'settings.updated')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertEquals($superAdmin->id, $log->user_id);
        $this->assertEquals($superAdmin->email, $log->user_email);
        $this->assertEquals('PlatformSetting', $log->resource_type);
        $this->assertEquals('general', $log->resource_id);

        // Verify sensitive key redaction
        $sanitized = AdminAuditService::sanitize([
            'site_name' => 'Audited Portal',
            'password' => 'supersecret123',
            'api_key' => 'secret_token_val',
        ]);
        $this->assertEquals('Audited Portal', $sanitized['site_name']);
        $this->assertEquals('[REDACTED]', $sanitized['password']);
        $this->assertEquals('[REDACTED]', $sanitized['api_key']);
    }

    /* =========================================================================
     * SECTION 7: ANALYTICS & LIVE PLAYERS HEARTBEAT
     * ========================================================================= */

    public function test_live_players_count_uses_active_sessions_and_ignores_expired_or_ended(): void
    {
        $cat = Category::factory()->create();
        $game = Game::factory()->create(['category_id' => $cat->id, 'status' => 'published']);
        $otherGame = Game::factory()->create(['category_id' => $cat->id, 'status' => 'published']);

        $service = app(AnalyticsService::class);
        Cache::forget('taph5_active_players_counts');

        // 1. Active player 1 (ping 30s ago, well within 90s cutoff)
        AnalyticsEvent::create([
            'event_type' => 'game_session_ping',
            'game_id' => $game->id,
            'visitor_id' => 'visitor-active-1',
            'session_id' => 'session-active-1',
            'device_type' => 'desktop',
            'created_at' => Carbon::now()->subSeconds(30),
        ]);

        // 2. Active player 2 (ping 45s ago, well within 90s cutoff)
        AnalyticsEvent::create([
            'event_type' => 'game_session_ping',
            'game_id' => $game->id,
            'visitor_id' => 'visitor-active-2',
            'session_id' => 'session-active-2',
            'device_type' => 'mobile',
            'created_at' => Carbon::now()->subSeconds(45),
        ]);

        // 3. Expired session (> 90 seconds ago, e.g. 180s)
        AnalyticsEvent::create([
            'event_type' => 'game_session_ping',
            'game_id' => $game->id,
            'visitor_id' => 'visitor-expired',
            'session_id' => 'session-expired',
            'device_type' => 'desktop',
            'created_at' => Carbon::now()->subSeconds(180),
        ]);

        // 4. Session that ended
        AnalyticsEvent::create([
            'event_type' => 'game_session_ping',
            'game_id' => $game->id,
            'visitor_id' => 'visitor-ended',
            'session_id' => 'session-ended',
            'device_type' => 'desktop',
            'created_at' => Carbon::now()->subSeconds(40),
        ]);
        AnalyticsEvent::create([
            'event_type' => 'game_session_ended',
            'game_id' => $game->id,
            'visitor_id' => 'visitor-ended',
            'session_id' => 'session-ended',
            'device_type' => 'desktop',
            'created_at' => Carbon::now()->subSeconds(20),
        ]);

        $activeCounts = $service->getActivePlayersCounts([$game->id, $otherGame->id]);

        $this->assertEquals(2, $activeCounts[$game->id]);
        $this->assertEquals(0, $activeCounts[$otherGame->id] ?? 0);
    }
}
