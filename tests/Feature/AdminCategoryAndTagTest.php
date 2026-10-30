<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Game;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCategoryAndTagTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_can_create_category(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/categories', [
            'name' => 'Strategy & Tactics',
            'slug' => 'strategy-tactics',
            'description' => 'Challenging mental games',
            'icon' => 'Brain',
            'status' => 'active',
            'sort_order' => 5,
        ]);

        $response->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseHas('categories', [
            'slug' => 'strategy-tactics',
            'name' => 'Strategy & Tactics',
        ]);
    }

    public function test_admin_can_update_category(): void
    {
        $category = Category::factory()->create(['name' => 'Old Cat', 'slug' => 'old-cat']);

        $response = $this->actingAs($this->admin)->put("/admin/categories/{$category->id}", [
            'name' => 'New Name Cat',
            'slug' => 'new-name-cat',
            'description' => 'Updated description',
            'status' => 'inactive',
            'sort_order' => 2,
        ]);

        $response->assertRedirect(route('admin.categories.index'));
        $fresh = $category->fresh();
        $this->assertEquals('New Name Cat', $fresh->name);
        $this->assertEquals('new-name-cat', $fresh->slug);
        $this->assertEquals('inactive', $fresh->status);
    }

    public function test_category_deletion_is_rejected_if_games_are_assigned(): void
    {
        $category = Category::factory()->create(['name' => 'Protected Category']);
        Game::factory()->create(['category_id' => $category->id]);

        $response = $this->actingAs($this->admin)->delete("/admin/categories/{$category->id}");

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_category_can_be_deleted_if_no_games_assigned(): void
    {
        $category = Category::factory()->create(['name' => 'Empty Category']);

        $response = $this->actingAs($this->admin)->delete("/admin/categories/{$category->id}");

        $response->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_admin_can_create_and_update_tags(): void
    {
        // Create tag
        $response = $this->actingAs($this->admin)->post('/admin/tags', [
            'name' => 'Multiplayer',
            'slug' => 'multiplayer',
        ]);

        $response->assertRedirect(route('admin.tags.index'));
        $this->assertDatabaseHas('tags', ['slug' => 'multiplayer']);

        $tag = Tag::where('slug', 'multiplayer')->first();

        // Update tag
        $responseUpdate = $this->actingAs($this->admin)->put("/admin/tags/{$tag->id}", [
            'name' => 'Single Player',
            'slug' => 'single-player',
        ]);

        $responseUpdate->assertRedirect(route('admin.tags.index'));
        $this->assertDatabaseHas('tags', ['slug' => 'single-player']);
    }

    public function test_tag_deletion_safely_detaches_from_games(): void
    {
        $tag = Tag::factory()->create(['name' => 'Detachable Tag']);
        $game = Game::factory()->create();
        $game->tags()->attach($tag->id);

        $this->assertDatabaseHas('game_tag', ['game_id' => $game->id, 'tag_id' => $tag->id]);

        $response = $this->actingAs($this->admin)->delete("/admin/tags/{$tag->id}");

        $response->assertRedirect(route('admin.tags.index'));
        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
        $this->assertDatabaseMissing('game_tag', ['tag_id' => $tag->id]);

        // Game itself still exists
        $this->assertDatabaseHas('games', ['id' => $game->id]);
    }

    public function test_public_category_route_remains_operational(): void
    {
        $category = Category::factory()->create(['slug' => 'puzzle', 'name' => 'Puzzle', 'status' => 'active']);
        Game::factory()->create(['category_id' => $category->id, 'status' => 'published']);

        $response = $this->get("/category/{$category->slug}");

        $response->assertStatus(200);
    }
}
