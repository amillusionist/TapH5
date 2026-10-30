<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Game;
use App\Services\Games\GamePublisherService;
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
        Config::set('games.embed_origin', 'https://games.taph5gaming.com');
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
        $this->assertEquals('https://games.taph5gaming.com/arcade/turbo-kart/index.html', $result['public_url']);

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
        $this->assertStringContainsString('cannot be approved: status is \'published\'', $result['message']);
    }

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

    public function test_publisher_rejects_destination_conflict(): void
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
