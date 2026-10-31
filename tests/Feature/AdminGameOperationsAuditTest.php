<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Game;
use App\Models\GameReview;
use App\Models\User;
use App\Services\Games\GameOperationsService;
use App\Services\Games\GamePublisherService;
use App\Services\Games\GameReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AdminGameOperationsAuditTest extends TestCase
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

        $this->stagingDir = storage_path('staging/audit-test-pkg');
        if (! File::isDirectory($this->stagingDir)) {
            File::makeDirectory($this->stagingDir, 0755, true);
        }

        File::put($this->stagingDir.'/index.html', '<!DOCTYPE html><html><head><title>Audit Test Game</title><meta name="description" content="Audit description"></head><body><canvas id="c"></canvas></body></html>');
        File::put($this->stagingDir.'/game.js', 'console.log("Game loaded");');
        File::put($this->stagingDir.'/thumbnail.png', 'fake-image-bytes');

        $subDir = $this->stagingDir.'/assets';
        if (! File::isDirectory($subDir)) {
            File::makeDirectory($subDir, 0755, true);
        }
        File::put($subDir.'/sound.mp3', 'fake-audio');
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

    // =========================================================================
    // 1. AUTHORIZATION AUDIT ACROSS ALL 6 ROLES
    // =========================================================================

    public function test_comprehensive_role_authorization_matrix(): void
    {
        $game = Game::factory()->pendingReview()->create([
            'title' => 'Auth Matrix Game',
            'storage_path' => 'staging/audit-test-pkg',
            'entry_file' => 'index.html',
            'category_id' => $this->category->id,
        ]);

        // 1. Guest -> redirected to login for all admin endpoints
        $this->get(route('admin.games.add'))->assertRedirect(route('login'));
        $this->get(route('admin.game_reviews.index'))->assertRedirect(route('login'));
        $this->get(route('admin.game_reviews.show', $game->id))->assertRedirect(route('login'));
        $this->post(route('admin.games.rescan', $game->id))->assertRedirect(route('login'));
        $this->get(route('admin.games.serve_staged', ['game' => $game->id]))->assertRedirect(route('login'));

        // 2. Regular User -> 403 Forbidden for all admin endpoints
        $this->actingAs($this->user)->get(route('admin.games.add'))->assertStatus(403);
        $this->actingAs($this->user)->get(route('admin.game_reviews.index'))->assertStatus(403);
        $this->actingAs($this->user)->get(route('admin.game_reviews.show', $game->id))->assertStatus(403);
        $this->actingAs($this->user)->post(route('admin.games.rescan', $game->id))->assertStatus(403);
        $this->actingAs($this->user)->get(route('admin.games.serve_staged', ['game' => $game->id]))->assertStatus(403);

        // 3. Analyst (Read-only) -> 403 on mutation endpoints and review queue
        $this->actingAs($this->analyst)->get(route('admin.games.add'))->assertStatus(403);
        $this->actingAs($this->analyst)->get(route('admin.game_reviews.index'))->assertStatus(403);
        $this->actingAs($this->analyst)->get(route('admin.game_reviews.show', $game->id))->assertStatus(403);
        $this->actingAs($this->analyst)->post(route('admin.games.rescan', $game->id))->assertStatus(403);
        $this->actingAs($this->analyst)->post(route('admin.game_reviews.approve', $game->id))->assertStatus(403);

        // 4. Content Manager -> Allowed review/content operations, BLOCKED from publishing
        $this->actingAs($this->contentManager)->get(route('admin.games.add'))->assertStatus(200);
        $this->actingAs($this->contentManager)->get(route('admin.game_reviews.index'))->assertStatus(200);
        $this->actingAs($this->contentManager)->get(route('admin.game_reviews.show', $game->id))->assertStatus(200);
        $this->actingAs($this->contentManager)->post(route('admin.games.rescan', $game->id))->assertRedirect();
        $this->actingAs($this->contentManager)->get(route('admin.games.serve_staged', ['game' => $game->id]))->assertStatus(200);

        // 5. Admin & Super Admin -> Full authority
        $this->actingAs($this->admin)->get(route('admin.games.add'))->assertStatus(200);
        $this->actingAs($this->superAdmin)->get(route('admin.games.add'))->assertStatus(200);
    }

    public function test_content_manager_cannot_publish_through_any_vector(): void
    {
        $approvedGame = Game::factory()->approvedReview()->create([
            'title' => 'Approved Game For Pub Test',
            'storage_path' => 'staging/audit-test-pkg',
            'category_id' => $this->category->id,
        ]);

        // Vector A: Direct POST to Review Queue publish
        $this->actingAs($this->contentManager)
            ->post(route('admin.game_reviews.publish', $approvedGame->id))
            ->assertStatus(403);

        // Vector B: Direct POST to Catalog publish
        $this->actingAs($this->contentManager)
            ->post(route('admin.games.publish', $approvedGame->id))
            ->assertStatus(403);

        // Vector C: Bulk publish action
        $this->actingAs($this->contentManager)
            ->post(route('admin.games.bulk'), [
                'action' => 'publish',
                'game_ids' => [$approvedGame->id],
            ])
            ->assertStatus(403);

        // Vector D: Direct Add Game publish
        $this->actingAs($this->contentManager)
            ->post(route('admin.games.store_staged'), [
                'folder' => 'audit-test-pkg',
                'title' => 'CM Bypass Attempt',
                'slug' => 'cm-bypass-attempt',
                'category_id' => $this->category->id,
                'action_type' => 'publish',
            ])
            ->assertStatus(403);

        $approvedGame->refresh();
        $this->assertEquals('draft', $approvedGame->status);
    }

    // =========================================================================
    // 2. STAGING PREVIEW SECURITY & TRAVERSAL AUDIT
    // =========================================================================

    public function test_staging_preview_security_and_traversal_defenses(): void
    {
        $game = Game::factory()->pendingReview()->create([
            'title' => 'Security Audit Game',
            'storage_path' => 'staging/audit-test-pkg',
            'entry_file' => 'index.html',
            'category_id' => $this->category->id,
        ]);

        $actor = $this->admin;

        // Traversal Vector 1: Standard relative traversal
        $this->actingAs($actor)
            ->get(route('admin.games.serve_staged', ['game' => $game->id, 'path' => '../../../etc/passwd']))
            ->assertStatus(403);

        // Traversal Vector 2: URL-encoded traversal
        $this->actingAs($actor)
            ->get(route('admin.games.serve_staged', ['game' => $game->id, 'path' => '%2e%2e%2f%2e%2e%2fapp/Models/User.php']))
            ->assertStatus(403);

        // Traversal Vector 3: Double-encoded traversal
        $this->actingAs($actor)
            ->get(route('admin.games.serve_staged', ['game' => $game->id, 'path' => '%252e%252e%252f%252e%252e%252f']))
            ->assertStatus(403);

        // Traversal Vector 4: Windows backslash traversal
        $this->actingAs($actor)
            ->get(route('admin.games.serve_staged', ['game' => $game->id, 'path' => '..\\..\\app\\Models\\User.php']))
            ->assertStatus(403);

        // Traversal Vector 5: Null byte injection
        $this->actingAs($actor)
            ->get(route('admin.games.serve_staged', ['game' => $game->id, 'path' => "index.html\0.js"]))
            ->assertStatus(403);

        // Traversal Vector 6: Absolute paths
        $this->actingAs($actor)
            ->get(route('admin.games.serve_staged', ['game' => $game->id, 'path' => '/etc/passwd']))
            ->assertStatus(403);

        $this->actingAs($actor)
            ->get(route('admin.games.serve_staged', ['game' => $game->id, 'path' => 'C:/Windows/System32']))
            ->assertStatus(403);

        // Traversal Vector 7: Directory request (must be a file, rejecting directory listing)
        $this->actingAs($actor)
            ->get(route('admin.games.serve_staged', ['game' => $game->id, 'path' => 'assets']))
            ->assertStatus(404);

        // Traversal Vector 8: Missing file
        $this->actingAs($actor)
            ->get(route('admin.games.serve_staged', ['game' => $game->id, 'path' => 'does-not-exist.js']))
            ->assertStatus(404);

        // Valid File: HTML & Security Headers
        $validHtmlResponse = $this->actingAs($actor)
            ->get(route('admin.games.serve_staged', ['game' => $game->id, 'path' => 'index.html']));

        $validHtmlResponse->assertStatus(200);
        $validHtmlResponse->assertHeader('Content-Security-Policy', "sandbox allow-scripts allow-forms allow-pointer-lock; default-src 'self' 'unsafe-inline' data: blob:;");
        $validHtmlResponse->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $validHtmlResponse->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('text/html', (string) $validHtmlResponse->headers->get('Content-Type'));

        // Valid Non-HTML File: JavaScript MIME check
        $validJsResponse = $this->actingAs($actor)
            ->get(route('admin.games.serve_staged', ['game' => $game->id, 'path' => 'game.js']));

        $validJsResponse->assertStatus(200);
        $this->assertStringContainsString('application/javascript', (string) $validJsResponse->headers->get('Content-Type'));

        // Valid Non-HTML File: Image MIME check
        $validPngResponse = $this->actingAs($actor)
            ->get(route('admin.games.serve_staged', ['game' => $game->id, 'path' => 'thumbnail.png']));

        $validPngResponse->assertStatus(200);
        $this->assertStringContainsString('image/png', (string) $validPngResponse->headers->get('Content-Type'));
    }

    // =========================================================================
    // 3. PACKAGE REPLACEMENT INTEGRITY & ATOMIC ROLLBACK
    // =========================================================================

    public function test_package_replacement_validation_and_rollback_on_failure(): void
    {
        $opsService = app(GameOperationsService::class);

        // Create published game with live files
        $game = Game::factory()->create([
            'title' => 'Live Replace Target',
            'slug' => 'live-replace-target',
            'status' => 'published',
            'storage_path' => 'games/arcade-classics/live-replace-target',
            'category_id' => $this->category->id,
        ]);

        $pubDir = public_path('games/arcade-classics/live-replace-target');
        File::ensureDirectoryExists($pubDir);
        File::put($pubDir.'/index.html', 'Original Live Game');
        File::put($pubDir.'/version.txt', 'v1.0');

        // Test 1: Replace with non-existent staging folder
        $res = $opsService->replacePackage($game, 'non-existent-staging-dir');
        $this->assertFalse($res['success']);
        $this->assertStringContainsString('does not exist', $res['message']);
        $this->assertEquals('Original Live Game', File::get($pubDir.'/index.html'));

        // Test 2: Replace with invalid staging folder missing entry point
        $invalidStaging = storage_path('staging/invalid-pkg');
        File::ensureDirectoryExists($invalidStaging);
        File::put($invalidStaging.'/readme.txt', 'no index.html here');

        $res2 = $opsService->replacePackage($game, 'invalid-pkg');
        $this->assertFalse($res2['success']);
        $this->assertStringContainsString('validation failed', strtolower($res2['message']));
        // Live files preserved!
        $this->assertEquals('Original Live Game', File::get($pubDir.'/index.html'));
        $this->assertEquals('v1.0', File::get($pubDir.'/version.txt'));

        // Clean invalid staging
        File::deleteDirectory($invalidStaging);

        // Test 3: Successful replacement with backup cleanup
        $res3 = $opsService->replacePackage($game, 'audit-test-pkg');
        $this->assertTrue($res3['success']);
        $this->assertStringContainsString('Audit Test Game', File::get($pubDir.'/index.html'));

        // Verify temp backups directory is empty
        $tempBackups = File::glob(storage_path('app/temp_backups/*'));
        $this->assertEmpty($tempBackups);

        // Audit review history logged
        $this->assertDatabaseHas('game_reviews', [
            'game_id' => $game->id,
            'action' => GameReview::ACTION_PACKAGE_REPLACED,
        ]);
    }

    public function test_package_replacement_concurrent_lock_protection(): void
    {
        $opsService = app(GameOperationsService::class);
        $game = Game::factory()->create(['title' => 'Lock Test Game']);

        // Simulate an existing lock acquired by a concurrent process
        $lock = Cache::lock("game_package_replace_{$game->id}", 30);
        $this->assertTrue($lock->get());

        // Attempt package replacement while locked
        $res = $opsService->replacePackage($game, 'audit-test-pkg');
        $this->assertFalse($res['success']);
        $this->assertStringContainsString('in progress', $res['message']);

        // Release lock
        $lock->release();
    }

    // =========================================================================
    // 4. REVIEW WORKFLOW INTEGRITY & STATE TRANSITION CONSTRAINTS
    // =========================================================================

    public function test_review_workflow_strictly_prevents_invalid_publishing(): void
    {
        $pubService = app(GamePublisherService::class);
        $reviewService = app(GameReviewService::class);

        // Case A: Rejected game cannot be published
        $rejectedGame = Game::factory()->rejectedReview()->create([
            'title' => 'Rejected Blocked Game',
            'storage_path' => 'staging/audit-test-pkg',
            'category_id' => $this->category->id,
        ]);

        $resA1 = $pubService->approveAndPublish($rejectedGame->id);
        $this->assertFalse($resA1['success']);
        $this->assertStringContainsString('rejected', $resA1['message']);

        $resA2 = $reviewService->approveAndPublish($rejectedGame, $this->admin);
        $this->assertFalse($resA2['success']);

        // Case B: Changes-requested game cannot be published directly
        $changesGame = Game::factory()->changesRequested()->create([
            'title' => 'Changes Blocked Game',
            'storage_path' => 'staging/audit-test-pkg',
            'category_id' => $this->category->id,
        ]);

        $resB1 = $pubService->approveAndPublish($changesGame->id);
        $this->assertFalse($resB1['success']);
        $this->assertStringContainsString('changes_requested', $resB1['message']);

        $resB2 = $reviewService->approveAndPublish($changesGame, $this->admin);
        $this->assertFalse($resB2['success']);

        // Case C: Pending game cannot be published via standard publish without approval
        $pendingGame = Game::factory()->pendingReview()->create([
            'title' => 'Pending Game Without Approval',
            'storage_path' => 'staging/audit-test-pkg',
            'category_id' => $this->category->id,
        ]);

        $resC = $this->actingAs($this->admin)->post(route('admin.games.publish', $pendingGame->id));
        $resC->assertRedirect();
        $this->assertStringContainsString('must be approved first', session('error'));

        $pendingGame->refresh();
        $this->assertEquals('draft', $pendingGame->status);
    }

    public function test_review_workflow_disallowed_state_transitions(): void
    {
        $reviewService = app(GameReviewService::class);

        // A. Cannot approve an already approved game
        $approvedGame = Game::factory()->approvedReview()->create();
        $resA = $reviewService->approve($approvedGame, $this->admin);
        $this->assertFalse($resA['success']);
        $this->assertStringContainsString('already approved', $resA['message']);

        // B. Cannot approve a rejected game directly (must submit revisions first)
        $rejectedGame = Game::factory()->rejectedReview()->create();
        $resB = $reviewService->approve($rejectedGame, $this->admin);
        $this->assertFalse($resB['success']);
        $this->assertStringContainsString('Rejected game', $resB['message']);

        // C. Cannot approve a game that has not been submitted
        $draftGame = Game::factory()->draft()->create(['review_status' => Game::REVIEW_NOT_SUBMITTED]);
        $resC = $reviewService->approve($draftGame, $this->admin);
        $this->assertFalse($resC['success']);
        $this->assertStringContainsString('must be submitted', $resC['message']);

        // D. Cannot request changes on a game that is already approved
        $resD = $reviewService->requestChanges($approvedGame, $this->admin, 'Some notes');
        $this->assertFalse($resD['success']);
        $this->assertStringContainsString('already approved', $resD['message']);

        // E. Cannot submit a game for review that is already pending
        $pendingGame = Game::factory()->pendingReview()->create();
        $resE = $reviewService->submitForReview($pendingGame, $this->contentManager);
        $this->assertFalse($resE['success']);
        $this->assertStringContainsString('already pending', $resE['message']);

        // F. Cannot return to draft a game that is already not submitted
        $resF = $reviewService->returnToDraft($draftGame, $this->admin);
        $this->assertFalse($resF['success']);
        $this->assertStringContainsString('already in draft', $resF['message']);
    }

    public function test_concurrent_review_collision_detection(): void
    {
        $reviewService = app(GameReviewService::class);
        $game = Game::factory()->pendingReview()->create();

        // Simulate Reviewer 1 loading game in pending state, but Reviewer 2 updates it in background to rejected
        $staleGameInstance = Game::find($game->id);

        // Reviewer 2 rejects it first
        $game->update(['review_status' => Game::REVIEW_REJECTED]);

        // Reviewer 1 tries to approve using the stale model state
        $res = $reviewService->approve($staleGameInstance, $this->admin);
        $this->assertFalse($res['success']);
    }

    public function test_review_notes_and_rejection_reasons_preserved(): void
    {
        $reviewService = app(GameReviewService::class);
        $game = Game::factory()->pendingReview()->create();

        // Request changes with documented requirements
        $reviewService->requestChanges($game, $this->admin, 'Controls fail on iPad Safari. Thumbnail must be 4:3.');
        $game->refresh();
        $this->assertEquals('Controls fail on iPad Safari. Thumbnail must be 4:3.', $game->review_notes);

        // Submitter resubmits with notes
        $reviewService->submitForReview($game, $this->contentManager, 'Updated touch handlers and regenerated 4:3 thumbnail.');
        $game->refresh();
        $this->assertEquals('Updated touch handlers and regenerated 4:3 thumbnail.', $game->review_notes);

        // Reject with formal reason
        $reviewService->reject($game, $this->admin, 'Copyrighted music detected in background soundtrack.');
        $game->refresh();
        $this->assertEquals('Copyrighted music detected in background soundtrack.', $game->review_notes);

        // Verify complete history in game_reviews table
        $reviews = GameReview::where('game_id', $game->id)->orderBy('id')->get();
        $this->assertCount(3, $reviews);
        $this->assertEquals('Controls fail on iPad Safari. Thumbnail must be 4:3.', $reviews[0]->notes);
        $this->assertEquals('Updated touch handlers and regenerated 4:3 thumbnail.', $reviews[1]->notes);
        $this->assertEquals('Copyrighted music detected in background soundtrack.', $reviews[2]->notes);
    }
}
