<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_settings_page(): void
    {
        /** @var User $superAdmin */
        $superAdmin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($superAdmin)->get('/admin/settings');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Settings/Index')
            ->has('settings')
        );
    }

    public function test_super_admin_can_update_general_and_gameplay_settings(): void
    {
        /** @var User $superAdmin */
        $superAdmin = User::factory()->superAdmin()->create();

        $response = $this->from('/admin/settings')->actingAs($superAdmin)->put('/admin/settings', [
            'group' => 'general',
            'site_name' => 'TapH5 Mega Arcade',
            'site_description' => 'Best Free Games Online Playable Anywhere',
            'contact_email' => 'support@taph5arcade.com',
            'default_language' => 'en',
            'default_timezone' => 'UTC',
        ]);

        $response->assertRedirect('/admin/settings');
        $this->assertDatabaseHas('platform_settings', [
            'key' => 'site_name',
            'value' => 'TapH5 Mega Arcade',
        ]);

        // Update gameplay group
        $responseGameplay = $this->from('/admin/settings')->actingAs($superAdmin)->put('/admin/settings', [
            'group' => 'gameplay',
            'default_games_per_page' => 24,
            'homepage_game_limit' => 12,
            'related_games_limit' => 8,
            'enable_recommendations' => true,
        ]);

        $responseGameplay->assertRedirect('/admin/settings');
        $this->assertDatabaseHas('platform_settings', [
            'key' => 'default_games_per_page',
            'value' => '24',
        ]);
    }

    public function test_regular_user_cannot_update_settings(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->put('/admin/settings', [
            'group' => 'general',
            'site_name' => 'Hacked Site',
            'site_description' => 'Hacked Description',
            'contact_email' => 'hacker@example.com',
            'default_language' => 'en',
            'default_timezone' => 'UTC',
        ]);

        $response->assertStatus(403);
    }
}
