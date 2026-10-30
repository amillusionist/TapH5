<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Game;
use App\Services\Games\GameScannerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class GameDuplicateAndDatabaseTest extends TestCase
{
    use RefreshDatabase;

    protected string $testStagingPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testStagingPath = storage_path('framework/testing/dup_staging_'.uniqid());
        File::makeDirectory($this->testStagingPath, 0755, true, true);
        Config::set('games.staging_root', $this->testStagingPath);
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->testStagingPath)) {
            File::deleteDirectory($this->testStagingPath);
        }
        parent::tearDown();
    }

    public function test_repeated_scan_does_not_create_duplicate_records(): void
    {
        $gameDir = $this->testStagingPath.'/idempotent-game';
        File::makeDirectory($gameDir, 0755, true);
        File::put($gameDir.'/index.html', '<!DOCTYPE html><html><head><title>Idempotent Game</title></head></html>');

        /** @var GameScannerService $scanner */
        $scanner = app(GameScannerService::class);

        // First scan: creates draft
        $result1 = $scanner->scan();
        $this->assertEquals(1, $result1['drafts_created']);
        $this->assertEquals(1, Game::where('slug', 'idempotent-game')->count());

        // Second scan: detects existing draft and updates metadata instead of duplicating
        $result2 = $scanner->scan();
        $this->assertEquals(0, $result2['drafts_created']);
        $this->assertEquals(1, $result2['drafts_updated']);
        $this->assertEquals(1, Game::where('slug', 'idempotent-game')->count());
    }

    public function test_exact_fingerprint_duplicate_in_different_folder_is_detected(): void
    {
        // Directory 1
        $gameDir1 = $this->testStagingPath.'/original-folder';
        File::makeDirectory($gameDir1, 0755, true);
        File::put($gameDir1.'/index.html', '<html><head><title>Same Content</title></head></html>');

        /** @var GameScannerService $scanner */
        $scanner = app(GameScannerService::class);
        $scanner->scan();
        $this->assertEquals(1, Game::count());

        // Directory 2: exact copy in another folder
        $gameDir2 = $this->testStagingPath.'/copied-folder';
        File::makeDirectory($gameDir2, 0755, true);
        File::put($gameDir2.'/index.html', '<html><head><title>Same Content</title></head></html>');

        $result2 = $scanner->scan();
        $this->assertEquals(1, $result2['duplicates']);
        $this->assertEquals(0, $result2['drafts_created']);
        $this->assertEquals(1, Game::count());
    }

    public function test_rescan_preserves_manually_edited_title_and_description(): void
    {
        $gameDir = $this->testStagingPath.'/edited-game';
        File::makeDirectory($gameDir, 0755, true);
        File::put($gameDir.'/index.html', '<!DOCTYPE html><html><head><title>Initial Title</title></head></html>');

        /** @var GameScannerService $scanner */
        $scanner = app(GameScannerService::class);
        $scanner->scan();

        $game = Game::where('slug', 'initial-title')->firstOrFail();
        $game->update([
            'title' => 'Custom Curated Title',
            'description' => 'Custom editorial description written by admin.',
        ]);

        // Rescan
        $scanner->scan();

        $freshGame = $game->fresh();
        $this->assertEquals('Custom Curated Title', $freshGame->title);
        $this->assertEquals('Custom editorial description written by admin.', $freshGame->description);
    }

    public function test_slug_conflict_with_different_content_generates_unique_slug(): void
    {
        // Existing published game with slug 'speed-runner'
        Category::factory()->create(['slug' => 'arcade']);
        Game::factory()->create([
            'title' => 'Speed Runner',
            'slug' => 'speed-runner',
            'status' => 'published',
            'fingerprint' => 'different-fingerprint-12345',
        ]);

        // New staged game having title 'Speed Runner'
        $gameDir = $this->testStagingPath.'/speed-runner';
        File::makeDirectory($gameDir, 0755, true);
        File::put($gameDir.'/index.html', '<html><head><title>Speed Runner</title></head><body>New different game</body></html>');

        /** @var GameScannerService $scanner */
        $scanner = app(GameScannerService::class);
        $result = $scanner->scan();

        $this->assertEquals(1, $result['drafts_created']);
        $newDraft = Game::where('title', 'Speed Runner')->where('status', 'draft')->first();
        $this->assertNotNull($newDraft);
        $this->assertNotEquals('speed-runner', $newDraft->slug);
        $this->assertStringStartsWith('speed-runner-', $newDraft->slug);
    }
}
