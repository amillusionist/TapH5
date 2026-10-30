<?php

namespace Tests\Feature;

use App\Models\AdPlacement;
use App\Models\AdProvider;
use App\Models\Category;
use App\Models\Game;
use App\Models\User;
use App\Services\Ads\AdService;
use Database\Seeders\AdPlacementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MonetizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdPlacementSeeder::class);

        $this->admin = User::factory()->admin()->create();
        $this->regularUser = User::factory()->create();
    }

    public function test_ads_are_disabled_by_default(): void
    {
        $adService = app(AdService::class);

        $this->assertFalse(config('ads.enabled'));
        $this->assertFalse($adService->isGloballyEnabled());
        $this->assertCount(0, $adService->getActivePlacements());
    }

    public function test_public_ad_config_returns_disabled_state_by_default(): void
    {
        $adService = app(AdService::class);
        $publicConfig = $adService->getPublicAdConfig();

        $this->assertFalse($publicConfig['ads_enabled']);
        $this->assertTrue($publicConfig['consent_required']);
        $this->assertEmpty($publicConfig['providers']);
    }

    public function test_adsense_provider_requires_valid_publisher_id(): void
    {
        $adService = app(AdService::class);
        $provider = $adService->getProvider('adsense');

        $this->assertNotNull($provider);
        // Default seeded provider has no publisher ID
        $this->assertFalse($provider->isConfigured());

        // Update with valid ca-pub- ID
        AdProvider::where('key', 'adsense')->update([
            'publisher_id' => 'ca-pub-1234567890123456',
        ]);
        $adService->clearCache();

        $updatedProvider = $adService->getProvider('adsense');
        $this->assertTrue($updatedProvider->isConfigured());
        $this->assertStringContainsString('ca-pub-1234567890123456', $updatedProvider->getScriptUrl() ?? '');
    }

    public function test_monetization_admin_page_requires_admin_authentication(): void
    {
        // Guests redirected to login
        $this->get('/admin/monetization')->assertRedirect('/login');

        // Regular users rejected with 403 Forbidden
        $this->actingAs($this->regularUser)->get('/admin/monetization')->assertStatus(403);

        // Admins successfully view monetization overview
        $response = $this->actingAs($this->admin)->get('/admin/monetization');
        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Monetization/Index')
            ->has('monetization.global')
            ->has('monetization.stats')
            ->has('monetization.providers')
            ->has('monetization.placements')
            ->where('monetization.stats.total_placements', 8)
        );
    }

    public function test_admin_can_update_provider_configuration(): void
    {
        $response = $this->actingAs($this->admin)->put('/admin/monetization/providers/adsense', [
            'is_enabled' => true,
            'publisher_id' => 'ca-pub-9999888877776666',
            'test_mode' => false,
            'settings' => ['responsive' => true],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('ad_providers', [
            'key' => 'adsense',
            'is_enabled' => true,
            'publisher_id' => 'ca-pub-9999888877776666',
            'test_mode' => false,
        ]);
    }

    public function test_admin_can_update_ad_placement_configuration(): void
    {
        $placement = AdPlacement::where('key', 'home_top')->firstOrFail();

        $response = $this->actingAs($this->admin)->put("/admin/monetization/placements/{$placement->id}", [
            'name' => 'Custom Homepage Leaderboard',
            'description' => 'Updated banner description',
            'provider' => 'adsense',
            'slot_id' => '9876543210',
            'placement_type' => 'banner',
            'is_enabled' => true,
            'show_on_desktop' => true,
            'show_on_mobile' => false,
            'frequency_limit' => 60,
            'priority' => 5,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('ad_placements', [
            'id' => $placement->id,
            'name' => 'Custom Homepage Leaderboard',
            'slot_id' => '9876543210',
            'is_enabled' => true,
            'show_on_mobile' => false,
        ]);
    }

    public function test_invalid_provider_in_placement_is_rejected(): void
    {
        $placement = AdPlacement::where('key', 'home_top')->firstOrFail();

        $response = $this->actingAs($this->admin)->put("/admin/monetization/placements/{$placement->id}", [
            'name' => 'Invalid Provider Test',
            'provider' => 'unsupported_ad_network',
            'placement_type' => 'display',
            'is_enabled' => true,
            'show_on_desktop' => true,
            'show_on_mobile' => true,
            'frequency_limit' => 0,
            'priority' => 0,
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['provider']);
    }

    public function test_public_pages_load_cleanly_with_ads_disabled(): void
    {
        $category = Category::factory()->create();
        $game = Game::factory()->create([
            'category_id' => $category->id,
            'slug' => 'test-monetization-game',
            'status' => 'published',
        ]);

        // Home
        $homeRes = $this->get('/');
        $homeRes->assertStatus(200);
        $homeRes->assertInertia(fn (Assert $page) => $page
            ->has('adConfig')
            ->where('adConfig.ads_enabled', false)
        );

        // Games Catalog
        $gamesRes = $this->get('/games');
        $gamesRes->assertStatus(200);
        $gamesRes->assertInertia(fn (Assert $page) => $page
            ->has('adConfig')
            ->where('adConfig.ads_enabled', false)
        );

        // Game Detail Page
        $gameRes = $this->get('/games/test-monetization-game');
        $gameRes->assertStatus(200);
        $gameRes->assertInertia(fn (Assert $page) => $page
            ->has('adConfig')
            ->where('adConfig.ads_enabled', false)
        );
    }

    public function test_monetization_consent_configuration_and_policy_version(): void
    {
        $adService = app(AdService::class);

        $this->assertSame('first_party', config('ads.consent_mode'));
        $this->assertSame('2026-10-31', config('ads.consent_policy_version'));

        $this->assertSame('first_party', $adService->getConsentMode());
        $this->assertSame('2026-10-31', $adService->getConsentPolicyVersion());

        $publicConfig = $adService->getPublicAdConfig();
        $this->assertSame('first_party', $publicConfig['consent_mode']);
        $this->assertSame('2026-10-31', $publicConfig['consent_policy_version']);
    }

    public function test_h5_games_ads_provider_returns_game_document_integration_and_prepared_status(): void
    {
        $adService = app(AdService::class);
        $provider = $adService->getProvider('h5_games_ads');

        $this->assertNotNull($provider);
        $this->assertFalse($provider->isConfigured());

        $config = $provider->getPublicConfig();
        $this->assertSame('game_document', $config['integration_model']);
        $this->assertTrue($config['requires_sdk']);

        // Check overview status when not configured
        $overview = $adService->getAdminOverview();
        $h5Overview = collect($overview['providers'])->firstWhere('key', 'h5_games_ads');
        $this->assertNotNull($h5Overview);
        $this->assertSame('Prepared (Requires Account Approval & Game SDK)', $h5Overview['status_text']);

        // When configured with client ID, status updates
        AdProvider::where('key', 'h5_games_ads')->update([
            'publisher_id' => 'ca-pub-1111222233334444',
            'is_enabled' => true,
        ]);
        $adService->clearCache();

        $updatedOverview = $adService->getAdminOverview();
        $updatedH5 = collect($updatedOverview['providers'])->firstWhere('key', 'h5_games_ads');
        $this->assertSame('Ready for Activation (Requires Game SDK)', $updatedH5['status_text']);
    }

    public function test_admin_monetization_overview_contains_consent_metadata(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/monetization');
        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Monetization/Index')
            ->where('monetization.global.consent_mode', 'first_party')
            ->where('monetization.global.consent_policy_version', '2026-10-31')
        );
    }

    public function test_privacy_and_cookie_policy_pages_render_successfully(): void
    {
        $privacyRes = $this->get('/privacy');
        $privacyRes->assertStatus(200);

        $cookieRes = $this->get('/cookie-policy');
        $cookieRes->assertStatus(200);
    }
}
