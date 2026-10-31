<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Game;
use App\Models\GameReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminGameOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $admin;

    protected User $contentManager;

    protected User $analyst;

    protected User $user;

    protected Category $category;

    protected string $stagingDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->superAdmin()->create();
        $this->admin = User::factory()->admin()->create();
        $this->contentManager = User::factory()->contentManager()->create();
        $this->analyst = User::factory()->analyst()->create();
        $this->user = User::factory()->create(['role' => 'user']);

        $this->category = Category::factory()->create([
            'name' => 'Arcade Classics',
            'slug' => 'arcade-classics',
        ]);

        // Ensure staging directory exists with a test folder
        $this->stagingDir = storage_path('staging/test-game-pkg');
        if (! File::isDirectory($this->stagingDir)) {
            File::makeDirectory($this->stagingDir, 0755, true);
        }

        File::put($this->stagingDir.'/index.html', '<!DOCTYPE html><html><head><title>Test Staged Game</title><meta name="description" content="A great test game"></head><body><canvas id="c"></canvas></body></html>');
        File::put($this->stagingDir.'/game.js', 'console.log("Game loaded");');
        File::put($this->stagingDir.'/thumbnail.png', 'fake-image-bytes');
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->stagingDir)) {
            File::deleteDirectory($this->stagingDir);
        }
        if (File::isDirectory(public_path('games/arcade-classics'))) {
            File::deleteDirectory(public_path('games/arcade-classics'));
        }
        parent::tearDown();
    }

    public function test_add_game_page_authorization(): void
    {
        // Guests redirected to login
        $this->get(route('admin.games.add'))->assertRedirect(route('login'));

        // Standard user gets 403
        $this->actingAs($this->user)->get(route('admin.games.add'))->assertStatus(403);

        // Analyst (read-only) gets 403
        $this->actingAs($this->analyst)->get(route('admin.games.add'))->assertStatus(403);

        // Content Manager, Admin, and Super Admin can access
        $this->actingAs($this->contentManager)->get(route('admin.games.add'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Games/Add')
                ->where('canPublish', false)
                ->has('stagingFolders')
            );

        $this->actingAs($this->admin)->get(route('admin.games.add'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Games/Add')
                ->where('canPublish', true)
            );
    }

    public function test_inspect_staging_folder_validates_and_extracts_metadata(): void
    {
        $response = $this->actingAs($this->contentManager)->postJson(route('admin.games.inspect_staging'), [
            'folder' => 'test-game-pkg',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'is_valid' => true,
            'folder' => 'test-game-pkg',
            'entry_file' => 'index.html',
            'title' => 'Test Staged Game',
            'description' => 'A great test game',
            'errors' => [],
        ]);
    }

    public function test_inspect_staging_folder_rejects_path_traversal(): void
    {
        $response = $this->actingAs($this->contentManager)->postJson(route('admin.games.inspect_staging'), [
            'folder' => '../../etc/passwd',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'is_valid' => false,
        ]);
        $this->assertStringContainsString('passwd', $response->json('errors.0'));
    }

    public function test_add_game_can_store_as_draft(): void
    {
        $response = $this->actingAs($this->contentManager)->post(route('admin.games.store_staged'), [
            'folder' => 'test-game-pkg',
            'title' => 'Imported Draft Game',
            'slug' => 'imported-draft-game',
            'description' => 'Draft description',
            'instructions' => 'Draft instructions',
            'category_id' => $this->category->id,
            'thumbnail' => 'thumbnail.png',
            'action_type' => 'draft',
        ]);

        $game = Game::where('slug', 'imported-draft-game')->first();
        $this->assertNotNull($game);
        $response->assertRedirect(route('admin.games.show', $game->id));

        $this->assertDatabaseHas('games', [
            'title' => 'Imported Draft Game',
            'slug' => 'imported-draft-game',
            'status' => 'draft',
            'review_status' => Game::REVIEW_NOT_SUBMITTED,
        ]);
    }

    public function test_add_game_can_submit_for_review_immediately(): void
    {
        $response = $this->actingAs($this->contentManager)->post(route('admin.games.store_staged'), [
            'folder' => 'test-game-pkg',
            'title' => 'Direct Review Submission',
            'slug' => 'direct-review-submission',
            'category_id' => $this->category->id,
            'action_type' => 'submit_for_review',
        ]);

        $game = Game::where('slug', 'direct-review-submission')->first();
        $this->assertNotNull($game);
        $this->assertEquals('draft', $game->status);
        $this->assertEquals(Game::REVIEW_PENDING, $game->review_status);
        $this->assertEquals($this->contentManager->id, $game->submitted_by);
        $this->assertNotNull($game->submitted_at);

        $this->assertDatabaseHas('game_reviews', [
            'game_id' => $game->id,
            'user_id' => $this->contentManager->id,
            'action' => GameReview::ACTION_SUBMITTED,
            'to_status' => Game::REVIEW_PENDING,
        ]);

        $response->assertRedirect(route('admin.game_reviews.index'));
    }

    public function test_content_manager_cannot_publish_directly_via_add_game(): void
    {
        $response = $this->actingAs($this->contentManager)->post(route('admin.games.store_staged'), [
            'folder' => 'test-game-pkg',
            'title' => 'Unauthorized Publish',
            'slug' => 'unauthorized-publish',
            'category_id' => $this->category->id,
            'action_type' => 'publish',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('games', ['slug' => 'unauthorized-publish']);
    }

    public function test_admin_can_publish_immediately_via_add_game(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.games.store_staged'), [
            'folder' => 'test-game-pkg',
            'title' => 'Admin Direct Publish',
            'slug' => 'admin-direct-publish',
            'category_id' => $this->category->id,
            'action_type' => 'publish',
        ]);

        $game = Game::where('slug', 'admin-direct-publish')->first();
        $this->assertNotNull($game);
        $this->assertEquals('published', $game->status);
        $this->assertEquals(Game::REVIEW_APPROVED, $game->review_status);
        $this->assertEquals($this->admin->id, $game->reviewed_by);

        $response->assertRedirect(route('admin.games.show', $game->id));
    }

    public function test_review_queue_listing_and_filtering(): void
    {
        // Seed review queue items
        Game::factory()->pendingReview()->create([
            'title' => 'Pending Alpha',
            'category_id' => $this->category->id,
        ]);
        Game::factory()->changesRequested()->create([
            'title' => 'Changes Beta',
            'category_id' => $this->category->id,
        ]);
        Game::factory()->approvedReview()->create([
            'title' => 'Approved Gamma',
            'category_id' => $this->category->id,
        ]);
        Game::factory()->rejectedReview()->create([
            'title' => 'Rejected Delta',
            'category_id' => $this->category->id,
        ]);

        // Default tab shows pending
        $response = $this->actingAs($this->admin)->get(route('admin.game_reviews.index'));
        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Reviews/Index')
            ->where('filters.status', 'pending')
            ->where('counts.pending', 1)
            ->where('counts.changes_requested', 1)
            ->where('counts.approved', 1)
            ->where('counts.rejected', 1)
            ->where('counts.all', 4)
            ->has('games.data', 1)
            ->where('games.data.0.title', 'Pending Alpha')
        );

        // Filter by changes_requested
        $response = $this->actingAs($this->admin)->get(route('admin.game_reviews.index', ['status' => 'changes_requested']));
        $response->assertInertia(fn (Assert $page) => $page
            ->has('games.data', 1)
            ->where('games.data.0.title', 'Changes Beta')
        );

        // Filter by search
        $response = $this->actingAs($this->admin)->get(route('admin.game_reviews.index', ['status' => 'all', 'search' => 'Gamma']));
        $response->assertInertia(fn (Assert $page) => $page
            ->has('games.data', 1)
            ->where('games.data.0.title', 'Approved Gamma')
        );
    }

    public function test_review_show_page_and_sandboxed_preview(): void
    {
        $game = Game::factory()->pendingReview()->create([
            'title' => 'Review Inspection Target',
            'storage_path' => 'staging/test-game-pkg',
            'entry_file' => 'index.html',
            'category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.game_reviews.show', $game->id));
        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Reviews/Show')
            ->where('game.title', 'Review Inspection Target')
            ->has('validation')
            ->has('duplicates')
            ->has('previewUrl')
        );

        // Test the sandboxed preview streaming route
        $previewResponse = $this->actingAs($this->admin)->get(route('admin.games.serve_staged', [
            'game' => $game->id,
            'path' => 'index.html',
        ]));

        $previewResponse->assertStatus(200);
        $previewResponse->assertHeader('Content-Security-Policy', "sandbox allow-scripts allow-forms allow-pointer-lock; default-src 'self' 'unsafe-inline' data: blob:;");
    }

    public function test_review_approval_and_publishing_workflow(): void
    {
        $game = Game::factory()->pendingReview()->create([
            'title' => 'Approval Workflow Game',
            'storage_path' => 'staging/test-game-pkg',
            'category_id' => $this->category->id,
        ]);

        // Content Manager approves (status remains draft, review_status becomes approved)
        $this->actingAs($this->contentManager)
            ->post(route('admin.game_reviews.approve', $game->id), [
                'notes' => 'Mechanics verified by QA.',
            ])
            ->assertRedirect();

        $game->refresh();
        $this->assertEquals(Game::REVIEW_APPROVED, $game->review_status);
        $this->assertEquals('draft', $game->status);
        $this->assertEquals($this->contentManager->id, $game->reviewed_by);

        // Content Manager cannot call publish endpoint (403)
        $this->actingAs($this->contentManager)
            ->post(route('admin.game_reviews.publish', $game->id))
            ->assertStatus(403);

        // Admin publishes game
        $this->actingAs($this->admin)
            ->post(route('admin.game_reviews.publish', $game->id), [
                'notes' => 'Publishing live to catalog.',
            ])
            ->assertRedirect(route('admin.games.show', $game->id));

        $game->refresh();
        $this->assertEquals('published', $game->status);
        $this->assertEquals(Game::REVIEW_APPROVED, $game->review_status);

        // Review audit trail recorded
        $this->assertDatabaseHas('game_reviews', [
            'game_id' => $game->id,
            'action' => GameReview::ACTION_PUBLISHED,
            'to_status' => 'published',
        ]);
    }

    public function test_review_request_changes_and_reject_actions(): void
    {
        $game = Game::factory()->pendingReview()->create([
            'title' => 'Revision Target',
            'category_id' => $this->category->id,
        ]);

        // Request changes
        $this->actingAs($this->admin)
            ->post(route('admin.game_reviews.request_changes', $game->id), [
                'notes' => 'Need updated mobile touch controls.',
            ])
            ->assertRedirect();

        $game->refresh();
        $this->assertEquals(Game::REVIEW_CHANGES_REQUESTED, $game->review_status);
        $this->assertEquals('Need updated mobile touch controls.', $game->review_notes);

        // Reject submission
        $this->actingAs($this->admin)
            ->post(route('admin.game_reviews.reject', $game->id), [
                'reason' => 'Package contains copyrighted assets.',
            ])
            ->assertRedirect();

        $game->refresh();
        $this->assertEquals(Game::REVIEW_REJECTED, $game->review_status);
        $this->assertEquals('Package contains copyrighted assets.', $game->review_notes);

        // Return to draft
        $this->actingAs($this->admin)
            ->post(route('admin.game_reviews.return_to_draft', $game->id))
            ->assertRedirect();

        $game->refresh();
        $this->assertEquals(Game::REVIEW_NOT_SUBMITTED, $game->review_status);
    }

    public function test_game_maintenance_operations(): void
    {
        $game = Game::factory()->create([
            'title' => 'Maintenance Game',
            'description' => null,
            'storage_path' => 'staging/test-game-pkg',
            'entry_file' => 'index.html',
            'category_id' => $this->category->id,
        ]);

        // Rescan files
        $this->actingAs($this->admin)
            ->post(route('admin.games.rescan', $game->id))
            ->assertRedirect();

        $game->refresh();
        $this->assertNotNull($game->fingerprint);

        // Revalidate endpoint (JSON response)
        $revalResponse = $this->actingAs($this->admin)
            ->postJson(route('admin.games.revalidate', $game->id));

        $revalResponse->assertStatus(200);
        $revalResponse->assertJson([
            'is_valid' => true,
            'entry_file' => 'index.html',
        ]);

        // Refresh metadata
        $this->actingAs($this->admin)
            ->post(route('admin.games.refresh_metadata', $game->id))
            ->assertRedirect();

        $game->refresh();
        $this->assertEquals('A great test game', $game->description);

        // Regenerate thumbnail
        $this->actingAs($this->admin)
            ->post(route('admin.games.regenerate_thumbnail', $game->id))
            ->assertRedirect();

        $game->refresh();
        $this->assertStringContainsString('thumbnail', (string) $game->thumbnail);
    }
}
