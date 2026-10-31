<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\HomepageSection;
use App\Models\HomepageSlide;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminHomepageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_homepage_management_screen(): void
    {
        /** @var User $admin */
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/admin/homepage');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Homepage/Index')
            ->has('sections')
            ->has('slides')
        );
    }

    public function test_admin_can_update_homepage_section(): void
    {
        /** @var User $admin */
        $admin = User::factory()->admin()->create();

        $section = HomepageSection::create([
            'section_key' => 'trending',
            'title' => 'Trending Games',
            'subtitle' => 'Play what everyone is playing',
            'is_enabled' => true,
            'sort_order' => 1,
            'items_limit' => 8,
        ]);

        $response = $this->from('/admin/homepage')->actingAs($admin)->put("/admin/homepage/sections/{$section->id}", [
            'title' => 'Hot & Trending Now',
            'subtitle' => 'Most played this week',
            'is_enabled' => false,
            'sort_order' => 1,
            'items_limit' => 12,
        ]);

        $response->assertRedirect('/admin/homepage');
        $this->assertDatabaseHas('homepage_sections', [
            'id' => $section->id,
            'title' => 'Hot & Trending Now',
            'subtitle' => 'Most played this week',
            'is_enabled' => false,
            'items_limit' => 12,
        ]);
    }

    public function test_admin_can_reorder_sections(): void
    {
        /** @var User $admin */
        $admin = User::factory()->admin()->create();

        $sec1 = HomepageSection::create(['section_key' => 'hero', 'title' => 'Hero', 'sort_order' => 1, 'items_limit' => 5, 'is_enabled' => true]);
        $sec2 = HomepageSection::create(['section_key' => 'popular', 'title' => 'Popular', 'sort_order' => 2, 'items_limit' => 8, 'is_enabled' => true]);

        $response = $this->from('/admin/homepage')->actingAs($admin)->post('/admin/homepage/sections/reorder', [
            'ordered_ids' => [$sec2->id, $sec1->id],
        ]);

        $response->assertRedirect('/admin/homepage');
        $this->assertEquals(1, $sec2->fresh()->sort_order);
        $this->assertEquals(2, $sec1->fresh()->sort_order);
    }

    public function test_admin_can_create_update_and_delete_hero_slides(): void
    {
        /** @var User $admin */
        $admin = User::factory()->admin()->create();
        $game = Game::factory()->create(['title' => 'Cyber Strike']);

        // Create slide
        $createResponse = $this->from('/admin/homepage')->actingAs($admin)->post('/admin/homepage/slides', [
            'game_id' => $game->id,
            'title' => 'Play Cyber Strike Today',
            'subtitle' => 'High octane cyber action',
            'cta_text' => 'Play Now',
            'cta_url' => "/games/{$game->slug}",
            'is_enabled' => true,
            'sort_order' => 1,
        ]);

        $createResponse->assertRedirect('/admin/homepage');
        $slide = HomepageSlide::where('title', 'Play Cyber Strike Today')->first();
        $this->assertNotNull($slide);

        // Update slide
        $updateResponse = $this->from('/admin/homepage')->actingAs($admin)->put("/admin/homepage/slides/{$slide->id}", [
            'game_id' => $game->id,
            'title' => 'Cyber Strike Deluxe',
            'subtitle' => 'Updated edition',
            'cta_text' => 'Launch',
            'cta_url' => "/games/{$game->slug}",
            'is_enabled' => false,
            'sort_order' => 2,
        ]);

        $updateResponse->assertRedirect('/admin/homepage');
        $this->assertEquals('Cyber Strike Deluxe', $slide->fresh()->title);
        $this->assertFalse($slide->fresh()->is_enabled);

        // Delete slide
        $deleteResponse = $this->from('/admin/homepage')->actingAs($admin)->delete("/admin/homepage/slides/{$slide->id}");
        $deleteResponse->assertRedirect('/admin/homepage');
        $this->assertDatabaseMissing('homepage_slides', ['id' => $slide->id]);
    }

    public function test_unauthorized_user_cannot_manage_homepage(): void
    {
        /** @var User $regularUser */
        $regularUser = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($regularUser)->get('/admin/homepage');
        $response->assertStatus(403);
    }
}
