<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Game;
use App\Services\Games\GameFingerprintService;
use App\Services\Games\GamePublisherService;
use App\Services\Games\GameValidatorService;
use App\Services\GameUrlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class GamePublisherTest extends TestCase
{
    use RefreshDatabase;

    protected string $testStagingPath;

    protected string $testPublicPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testStagingPath = storage_path('framework/testing/pub_staging_'.uniqid());
        $this->testPublicPath = storage_path('framework/testing/pub_public_'.uniqid());

        File::makeDirectory($this->testStagingPath, 0755, true, true);
        File::makeDirectory($this->testPublicPath, 0755, true, true);

        Config::set('games.staging_root', $this->testStagingPath);
        Config::set('games.public_root', $this->testPublicPath);
        Config::set('games.embed_origin', 'https://taph5gaming.com');
        Config::set('games.url_prefix', 'games');
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->testStagingPath)) {
            File::deleteDirectory($this->testStagingPath);
        }
        if (File::isDirectory($this->testPublicPath)) {
            File::deleteDirectory($this->testPublicPath);
        }
        parent::tearDown();
    }

    /**
     * 1. Successful publishing to a new destination.
     */
    public function test_publisher_service_approves_and_publishes_draft_game(): void
    {
        $arcade = Category::factory()->create(['slug' => 'arcade', 'name' => 'Arcade']);

        $gameDir = $this->testStagingPath.'/turbo-kart';
        File::makeDirectory($gameDir.'/assets', 0755, true);
        File::put($gameDir.'/index.html', '<!DOCTYPE html><html><head><title>Turbo Kart</title></head><body><h1>Kart</h1></body></html>');
        File::put($gameDir.'/assets/style.css', 'body { margin: 0; }');

        $draft = Game::factory()->create([
            'title' => 'Turbo Kart',
            'slug' => 'turbo-kart',
            'category_id' => $arcade->id,
            'status' => 'draft',
            'storage_path' => 'staging/turbo-kart',
            'entry_file' => 'index.html',
        ]);

        /** @var GamePublisherService $publisher */
        $publisher = app(GamePublisherService::class);
        $result = $publisher->approveAndPublish($draft->id);

        $this->assertTrue($result['success']);
        $this->assertEquals('arcade/turbo-kart', $result['public_storage_path']);
        $this->assertEquals('https://taph5gaming.com/games/arcade/turbo-kart/index.html', $result['public_url']);

        // Verify database update
        $fresh = $draft->fresh();
        $this->assertEquals('published', $fresh->status);
        $this->assertEquals('arcade/turbo-kart', $fresh->storage_path);

        // Verify destination files were copied and preserved
        $destinationDir = $this->testPublicPath.'/arcade/turbo-kart';
        $this->assertDirectoryExists($destinationDir);
        $this->assertFileExists($destinationDir.'/index.html');
        $this->assertFileExists($destinationDir.'/assets/style.css');
    }

    /**
     * 2. Destination directory already exists without a database record.
     */
    public function test_publisher_rejects_when_destination_directory_already_exists_without_database_record(): void
    {
        $arcade = Category::factory()->create(['slug' => 'arcade']);

        // Create staged source folder
        $gameDir = $this->testStagingPath.'/orphaned-folder';
        File::makeDirectory($gameDir, 0755, true);
        File::put($gameDir.'/index.html', '<!DOCTYPE html><html><head><title>Orphaned</title></head></html>');

        // Create destination directory without any DB record
        $destDir = $this->testPublicPath.'/arcade/orphaned-folder';
        File::makeDirectory($destDir, 0755, true);

        $draft = Game::factory()->create([
            'title' => 'Orphaned Game',
            'slug' => 'orphaned-folder',
            'category_id' => $arcade->id,
            'status' => 'draft',
            'storage_path' => 'staging/orphaned-folder',
            'entry_file' => 'index.html',
        ]);

        /** @var GamePublisherService $publisher */
        $publisher = app(GamePublisherService::class);
        $result = $publisher->approveAndPublish($draft->id);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Destination conflict', $result['message']);
        $this->assertStringContainsString('already exists on the filesystem', $result['message']);
        $this->assertEquals('draft', $draft->fresh()->status);
    }

    /**
     * 3 & 7. Destination contains unrelated files; failed publishing preserves pre-existing contents.
     */
    public function test_publisher_preserves_unrelated_files_when_destination_already_exists(): void
    {
        $arcade = Category::factory()->create(['slug' => 'arcade']);

        // Create staged source folder
        $gameDir = $this->testStagingPath.'/pre-existing-dir';
        File::makeDirectory($gameDir, 0755, true);
        File::put($gameDir.'/index.html', '<!DOCTYPE html><html><head><title>Staged</title></head></html>');

        // Create pre-existing destination folder containing unrelated files
        $destDir = $this->testPublicPath.'/arcade/pre-existing-dir';
        File::makeDirectory($destDir, 0755, true);
        $unrelatedFile = $destDir.'/unrelated_data.txt';
        File::put($unrelatedFile, 'CRITICAL UNRELATED CONTENT DO NOT OVERWRITE');

        $draft = Game::factory()->create([
            'title' => 'Collision Draft',
            'slug' => 'pre-existing-dir',
            'category_id' => $arcade->id,
            'status' => 'draft',
            'storage_path' => 'staging/pre-existing-dir',
            'entry_file' => 'index.html',
        ]);

        /** @var GamePublisherService $publisher */
        $publisher = app(GamePublisherService::class);
        $result = $publisher->approveAndPublish($draft->id);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Destination conflict', $result['message']);

        // Verify the pre-existing file was NOT overwritten or deleted
        $this->assertFileExists($unrelatedFile);
        $this->assertEquals('CRITICAL UNRELATED CONTENT DO NOT OVERWRITE', File::get($unrelatedFile));
        $this->assertEquals('draft', $draft->fresh()->status);
    }

    /**
     * 4. Destination path points to an existing file.
     */
    public function test_publisher_rejects_when_destination_path_is_an_existing_file(): void
    {
        $arcade = Category::factory()->create(['slug' => 'arcade']);

        // Create staged source folder
        $gameDir = $this->testStagingPath.'/file-collision';
        File::makeDirectory($gameDir, 0755, true);
        File::put($gameDir.'/index.html', '<!DOCTYPE html><html><head><title>File Collision</title></head></html>');

        // Create category dir and a regular file at the exact destination path
        File::makeDirectory($this->testPublicPath.'/arcade', 0755, true);
        $destFile = $this->testPublicPath.'/arcade/file-collision';
        File::put($destFile, 'I am an existing regular file');

        $draft = Game::factory()->create([
            'title' => 'File Collision Draft',
            'slug' => 'file-collision',
            'category_id' => $arcade->id,
            'status' => 'draft',
            'storage_path' => 'staging/file-collision',
            'entry_file' => 'index.html',
        ]);

        /** @var GamePublisherService $publisher */
        $publisher = app(GamePublisherService::class);
        $result = $publisher->approveAndPublish($draft->id);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Destination conflict', $result['message']);
        $this->assertFileExists($destFile);
        $this->assertEquals('I am an existing regular file', File::get($destFile));
        $this->assertEquals('draft', $draft->fresh()->status);
    }

    /**
     * 5. Source directory is missing.
     */
    public function test_publisher_rejects_if_staged_source_folder_is_missing(): void
    {
        $draft = Game::factory()->create([
            'slug' => 'ghost-game',
            'status' => 'draft',
            'storage_path' => 'staging/ghost-game',
        ]);

        /** @var GamePublisherService $publisher */
        $publisher = app(GamePublisherService::class);
        $result = $publisher->approveAndPublish($draft->id);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Staged source directory not found', $result['message']);
        $this->assertEquals('draft', $draft->fresh()->status);
    }

    /**
     * 6. Publishing fails midway through copying; verified rollback only removes files created during attempt.
     */
    public function test_publisher_rolls_back_created_files_if_copy_fails_midway(): void
    {
        $arcade = Category::factory()->create(['slug' => 'arcade']);

        $gameDir = $this->testStagingPath.'/failing-copy-game';
        File::makeDirectory($gameDir, 0755, true);
        File::put($gameDir.'/index.html', '<html></html>');
        File::put($gameDir.'/game.js', 'console.log("game");');
        File::put($gameDir.'/style.css', 'body{}');

        $draft = Game::factory()->create([
            'title' => 'Failing Copy Game',
            'slug' => 'failing-copy-game',
            'category_id' => $arcade->id,
            'status' => 'draft',
            'storage_path' => 'staging/failing-copy-game',
            'entry_file' => 'index.html',
        ]);

        // Custom publisher subclass that fails on the second file copy
        $failingPublisher = new class(app(GameValidatorService::class), app(GameFingerprintService::class), app(GameUrlService::class)) extends GamePublisherService
        {
            public int $copiedFiles = 0;

            protected function copyFile(string $sourceFile, string $targetFile): bool
            {
                $this->copiedFiles++;
                if ($this->copiedFiles > 1) {
                    return false; // Simulate copy failure on second file
                }

                return parent::copyFile($sourceFile, $targetFile);
            }
        };

        $result = $failingPublisher->approveAndPublish($draft->id);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Filesystem copy error', $result['message']);

        // Verify rollback cleaned up partially copied files
        $destDir = $this->testPublicPath.'/arcade/failing-copy-game';
        $this->assertDirectoryDoesNotExist($destDir);
        $this->assertEquals('draft', $draft->fresh()->status);
    }

    /**
     * 8. Successful publishing does not create nested games/games directories.
     */
    public function test_publisher_uses_general_category_fallback_to_prevent_nested_games_directory(): void
    {
        $gameDir = $this->testStagingPath.'/uncategorized-game';
        File::makeDirectory($gameDir, 0755, true);
        File::put($gameDir.'/index.html', '<!DOCTYPE html><html><head><title>Uncategorized</title></head></html>');

        $draft = Game::factory()->create([
            'title' => 'Uncategorized',
            'slug' => 'uncategorized-game',
            'category_id' => null,
            'status' => 'draft',
            'storage_path' => 'staging/uncategorized-game',
            'entry_file' => 'index.html',
        ]);

        /** @var GamePublisherService $publisher */
        $publisher = app(GamePublisherService::class);
        $result = $publisher->approveAndPublish($draft->id);

        $this->assertTrue($result['success']);
        $this->assertEquals('general/uncategorized-game', $result['public_storage_path']);
        $this->assertEquals('https://taph5gaming.com/games/general/uncategorized-game/index.html', $result['public_url']);
        $this->assertDirectoryExists($this->testPublicPath.'/general/uncategorized-game');
        $this->assertDirectoryDoesNotExist($this->testPublicPath.'/games/uncategorized-game');
    }

    /**
     * 9. Destination traversal attempt is rejected.
     */
    public function test_publisher_rejects_destination_traversal_attempts(): void
    {
        $draft = Game::factory()->create([
            'title' => 'Traversal Game',
            'slug' => '../../malicious-escape',
            'category_id' => null,
            'status' => 'draft',
            'storage_path' => 'staging/traversal',
            'entry_file' => 'index.html',
        ]);

        /** @var GamePublisherService $publisher */
        $publisher = app(GamePublisherService::class);
        $result = $publisher->approveAndPublish($draft->id);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Path traversal detected', $result['message']);
        $this->assertEquals('draft', $draft->fresh()->status);
    }

    /**
     * 10. Existing published games cannot be accidentally overwritten.
     */
    public function test_publisher_rejects_already_published_game(): void
    {
        $game = Game::factory()->create([
            'status' => 'published',
            'storage_path' => 'arcade/already-live',
        ]);

        /** @var GamePublisherService $publisher */
        $publisher = app(GamePublisherService::class);
        $result = $publisher->approveAndPublish($game->id);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString("cannot be approved: status is 'published'", $result['message']);
    }

    public function test_publisher_rejects_destination_conflict_with_another_published_game(): void
    {
        $arcade = Category::factory()->create(['slug' => 'arcade']);

        // Staged folder for draft
        $gameDir = $this->testStagingPath.'/shared-slug';
        File::makeDirectory($gameDir, 0755, true);
        File::put($gameDir.'/index.html', '<html></html>');

        // Existing published game already at destination
        Game::factory()->create([
            'slug' => 'old-published-game',
            'category_id' => $arcade->id,
            'status' => 'published',
            'storage_path' => 'arcade/shared-slug',
        ]);

        $draft = Game::factory()->create([
            'slug' => 'shared-slug',
            'category_id' => $arcade->id,
            'status' => 'draft',
            'storage_path' => 'staging/shared-slug',
            'entry_file' => 'index.html',
        ]);

        // Create pre-existing destination folder
        File::makeDirectory($this->testPublicPath.'/arcade/shared-slug', 0755, true);

        /** @var GamePublisherService $publisher */
        $publisher = app(GamePublisherService::class);
        $result = $publisher->approveAndPublish($draft->id);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Destination conflict', $result['message']);
        $this->assertEquals('draft', $draft->fresh()->status);
    }

    public function test_artisan_command_games_approve_works_via_cli(): void
    {
        $arcade = Category::factory()->create(['slug' => 'arcade']);

        $gameDir = $this->testStagingPath.'/cli-approve-game';
        File::makeDirectory($gameDir, 0755, true);
        File::put($gameDir.'/index.html', '<!DOCTYPE html><html><head><title>CLI Approved</title></head></html>');

        $draft = Game::factory()->create([
            'title' => 'CLI Approved',
            'slug' => 'cli-approve-game',
            'category_id' => $arcade->id,
            'status' => 'draft',
            'storage_path' => 'staging/cli-approve-game',
            'entry_file' => 'index.html',
        ]);

        $this->artisan('games:approve cli-approve-game')
            ->expectsOutputToContain('Game Successfully Approved & Published!')
            ->assertExitCode(0);

        $this->assertEquals('published', $draft->fresh()->status);
    }
}
