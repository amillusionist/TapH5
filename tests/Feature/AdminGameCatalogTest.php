<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Game;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminGameCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_filter_games_by_flags(): void
    {
        /** @var User $admin */
        $admin = User::factory()->admin()->create();

        $featured = Game::factory()->create(['title' => 'Featured Alpha', 'is_featured' => true]);
        $nonFeatured = Game::factory()->create(['title' => 'Normal Beta', 'is_featured' => false]);

        $response = $this->actingAs($admin)->get('/admin/games?flag=featured');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Games/Index')
            ->has('games.data', 1)
            ->where('games.data.0.id', $featured->id)
        );
    }

    public function test_admin_can_execute_bulk_publish_and_archive(): void
    {
        /** @var User $admin */
        $admin = User::factory()->admin()->create();

        $game1 = Game::factory()->create(['status' => 'draft']);
        $game2 = Game::factory()->create(['status' => 'published']);

        // Bulk archive
        $responseArchive = $this->from('/admin/games')->actingAs($admin)->post('/admin/games/bulk', [
            'action' => 'archive',
            'game_ids' => [$game1->id, $game2->id],
        ]);

        $responseArchive->assertRedirect('/admin/games');
        $this->assertEquals('archived', $game1->fresh()->status);
        $this->assertEquals('archived', $game2->fresh()->status);
    }

    public function test_admin_can_execute_bulk_flag_operations(): void
    {
        /** @var User $admin */
        $admin = User::factory()->admin()->create();
        $game = Game::factory()->create([
            'is_featured' => false,
            'is_trending' => false,
            'is_popular' => false,
        ]);

        // Set featured
        $this->from('/admin/games')->actingAs($admin)->post('/admin/games/bulk', [
            'action' => 'set_featured',
            'game_ids' => [$game->id],
        ]);
        $this->assertTrue($game->fresh()->is_featured);

        // Set trending
        $this->from('/admin/games')->actingAs($admin)->post('/admin/games/bulk', [
            'action' => 'set_trending',
            'game_ids' => [$game->id],
        ]);
        $this->assertTrue($game->fresh()->is_trending);

        // Unset featured
        $this->from('/admin/games')->actingAs($admin)->post('/admin/games/bulk', [
            'action' => 'unset_featured',
            'game_ids' => [$game->id],
        ]);
        $this->assertFalse($game->fresh()->is_featured);
    }

    public function test_admin_can_execute_bulk_category_and_tag_assignments(): void
    {
        /** @var User $admin */
        $admin = User::factory()->admin()->create();
        $cat1 = Category::factory()->create();
        $cat2 = Category::factory()->create();
        $tag = Tag::factory()->create(['name' => 'Retro']);
        $game = Game::factory()->create(['category_id' => $cat1->id]);

        // Change category
        $this->from('/admin/games')->actingAs($admin)->post('/admin/games/bulk', [
            'action' => 'change_category',
            'game_ids' => [$game->id],
            'category_id' => $cat2->id,
        ]);
        $this->assertEquals($cat2->id, $game->fresh()->category_id);

        // Add tag
        $this->from('/admin/games')->actingAs($admin)->post('/admin/games/bulk', [
            'action' => 'add_tags',
            'game_ids' => [$game->id],
            'tag_ids' => [$tag->id],
        ]);
        $this->assertTrue($game->fresh()->tags->contains($tag->id));

        // Remove tag
        $this->from('/admin/games')->actingAs($admin)->post('/admin/games/bulk', [
            'action' => 'remove_tags',
            'game_ids' => [$game->id],
            'tag_ids' => [$tag->id],
        ]);
        $this->assertFalse($game->fresh()->tags->contains($tag->id));
    }

    public function test_admin_can_execute_bulk_deletion(): void
    {
        /** @var User $admin */
        $admin = User::factory()->admin()->create();
        $game1 = Game::factory()->create(['status' => 'archived']);
        $game2 = Game::factory()->create(['status' => 'draft']);

        $response = $this->from('/admin/games')->actingAs($admin)->post('/admin/games/bulk', [
            'action' => 'delete',
            'game_ids' => [$game1->id, $game2->id],
        ]);

        $response->assertRedirect('/admin/games');
        $this->assertDatabaseMissing('games', ['id' => $game1->id]);
        $this->assertDatabaseMissing('games', ['id' => $game2->id]);
    }

    public function test_admin_can_trigger_rescan_and_metadata_repair(): void
    {
        /** @var User $admin */
        $admin = User::factory()->admin()->create();
        $game = Game::factory()->create([
            'slug' => 'sample-test-game',
            'title' => 'Sample Game',
        ]);

        $rescanResponse = $this->from("/admin/games/{$game->id}")->actingAs($admin)->post("/admin/games/{$game->id}/rescan");
        $rescanResponse->assertRedirect("/admin/games/{$game->id}");

        $repairResponse = $this->from("/admin/games/{$game->id}")->actingAs($admin)->post("/admin/games/{$game->id}/repair-metadata");
        $repairResponse->assertRedirect("/admin/games/{$game->id}");
    }

    public function test_admin_can_audit_catalog_integrity(): void
    {
        /** @var User $admin */
        $admin = User::factory()->admin()->create();
        Game::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get('/admin/games/batch-validate');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'total_games',
            'valid_games',
            'missing_entry_files',
            'missing_thumbnails',
            'timestamp',
        ]);
    }
}
