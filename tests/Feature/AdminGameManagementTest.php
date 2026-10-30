<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Game;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminGameManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_can_view_games_index_with_filters_and_counts(): void
    {
        $arcade = Category::factory()->create(['name' => 'Arcade', 'slug' => 'arcade']);
        $puzzle = Category::factory()->create(['name' => 'Puzzle', 'slug' => 'puzzle']);

        Game::factory()->create(['title' => 'Alpha Racer', 'category_id' => $arcade->id, 'status' => 'published']);
        Game::factory()->draft()->create(['title' => 'Beta Snake', 'category_id' => $puzzle->id]);
        Game::factory()->archived()->create(['title' => 'Gamma Quest', 'category_id' => $arcade->id]);

        $response = $this->actingAs($this->admin)->get('/admin/games');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Games/Index')
            ->has('games.data', 3)
            ->where('statusCounts.all', 3)
            ->where('statusCounts.published', 1)
            ->where('statusCounts.draft', 1)
            ->where('statusCounts.archived', 1)
        );
    }

    public function test_admin_can_search_and_filter_games_listing(): void
    {
        $arcade = Category::factory()->create(['slug' => 'arcade']);
        $puzzle = Category::factory()->create(['slug' => 'puzzle']);

        Game::factory()->create(['title' => 'Super Mario', 'category_id' => $arcade->id, 'status' => 'published']);
        Game::factory()->create(['title' => 'Cyber Snake', 'category_id' => $puzzle->id, 'status' => 'published']);

        // Search test
        $response = $this->actingAs($this->admin)->get('/admin/games?search=Mario');
        $response->assertInertia(fn (Assert $page) => $page
            ->has('games.data', 1)
            ->where('games.data.0.title', 'Super Mario')
        );

        // Category filter test
        $responseCat = $this->actingAs($this->admin)->get('/admin/games?category=puzzle');
        $responseCat->assertInertia(fn (Assert $page) => $page
            ->has('games.data', 1)
            ->where('games.data.0.title', 'Cyber Snake')
        );

        // Status filter test
        $responseStatus = $this->actingAs($this->admin)->get('/admin/games?status=draft');
        $responseStatus->assertInertia(fn (Assert $page) => $page
            ->has('games.data', 0)
        );
    }

    public function test_admin_can_inspect_game_details_and_scan_metadata(): void
    {
        $game = Game::factory()->create([
            'title' => 'Inspectable Game',
            'fingerprint' => 'sha256-test-fp',
            'scan_metadata' => ['files' => 5, 'scanned_at' => '2026-10-30'],
        ]);

        $response = $this->actingAs($this->admin)->get("/admin/games/{$game->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Games/Show')
            ->where('game.title', 'Inspectable Game')
            ->where('game.fingerprint', 'sha256-test-fp')
            ->has('game.scan_metadata')
        );
    }

    public function test_admin_can_update_game_metadata_and_sync_tags(): void
    {
        $oldCategory = Category::factory()->create(['name' => 'Old Category']);
        $newCategory = Category::factory()->create(['name' => 'New Category']);
        $tag1 = Tag::factory()->create(['name' => 'Retro']);
        $tag2 = Tag::factory()->create(['name' => 'Pixel']);

        $game = Game::factory()->create([
            'title' => 'Original Title',
            'slug' => 'original-title',
            'category_id' => $oldCategory->id,
            'storage_path' => 'arcade/original-title',
            'entry_file' => 'index.html',
        ]);

        $response = $this->actingAs($this->admin)->put("/admin/games/{$game->id}", [
            'title' => 'Updated Game Deluxe',
            'slug' => 'updated-game-deluxe',
            'category_id' => $newCategory->id,
            'description' => 'Brand new description',
            'instructions' => 'Touch or WASD to play',
            'thumbnail' => '/images/thumbnails/updated.png',
            'is_featured' => true,
            'is_trending' => true,
            'is_popular' => false,
            'seo_title' => 'Custom SEO Title',
            'seo_description' => 'Custom SEO Description',
            'tags' => [$tag1->id, $tag2->id],
            // Malicious attempts to overwrite system columns should be ignored
            'storage_path' => 'hacked/path',
            'entry_file' => 'malicious.php',
            'status' => 'draft',
        ]);

        $response->assertRedirect(route('admin.games.show', $game->id));

        $fresh = $game->fresh();
        $this->assertEquals('Updated Game Deluxe', $fresh->title);
        $this->assertEquals('updated-game-deluxe', $fresh->slug);
        $this->assertEquals($newCategory->id, $fresh->category_id);
        $this->assertEquals('Brand new description', $fresh->description);
        $this->assertTrue($fresh->is_featured);
        $this->assertTrue($fresh->is_trending);
        $this->assertEquals('Custom SEO Title', $fresh->seo_title);

        // Verify protected fields were NOT modified
        $this->assertEquals('arcade/original-title', $fresh->storage_path);
        $this->assertEquals('index.html', $fresh->entry_file);
        $this->assertEquals('published', $fresh->status);

        // Verify tags synced
        $this->assertCount(2, $fresh->tags);
        $this->assertTrue($fresh->tags->contains($tag1));
        $this->assertTrue($fresh->tags->contains($tag2));
    }

    public function test_game_update_validates_required_fields_and_slug_uniqueness(): void
    {
        $existing = Game::factory()->create(['slug' => 'occupied-slug']);
        $game = Game::factory()->create(['slug' => 'my-game']);

        $response = $this->actingAs($this->admin)->put("/admin/games/{$game->id}", [
            'title' => '',
            'slug' => 'occupied-slug',
        ]);

        $response->assertSessionHasErrors(['title', 'slug']);
    }
}

