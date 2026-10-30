<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Game;
use App\Services\Games\GameScannerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class GameScannerTest extends TestCase
{
    use RefreshDatabase;

    protected string $testStagingPath;

    protected string $testPublicPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testStagingPath = storage_path('framework/testing/staging_'.uniqid());
        $this->testPublicPath = storage_path('framework/testing/public_games_'.uniqid());

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

    public function test_scanner_handles_empty_staging_directory(): void
    {
        /** @var GameScannerService $scanner */
        $scanner = app(GameScannerService::class);

        $result = $scanner->scan();

        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['discovered']);
        $this->assertEquals(0, $result['drafts_created']);
        $this->assertEmpty($result['results']);
    }

    public function test_scanner_discovers_single_file_html_game(): void
    {
        $gameDir = $this->testStagingPath.'/single-pulse';
        File::makeDirectory($gameDir, 0755, true);
        File::put($gameDir.'/index.html', '<!DOCTYPE html><html><head><title>Single Pulse</title><meta name="description" content="Single file arcade game"></head><body><h1>Play</h1></body></html>');

        Category::factory()->create(['name' => 'Arcade', 'slug' => 'arcade', 'status' => 'active']);

        /** @var GameScannerService $scanner */
        $scanner = app(GameScannerService::class);
        $result = $scanner->scan();

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['discovered']);
        $this->assertEquals(1, $result['drafts_created']);

        $draft = Game::where('slug', 'single-pulse')->first();
        $this->assertNotNull($draft);
        $this->assertEquals('draft', $draft->status);
        $this->assertEquals('Single Pulse', $draft->title);
        $this->assertEquals('single_file', $draft->game_type);
        $this->assertEquals('index.html', $draft->entry_file);
        $this->assertNotEmpty($draft->fingerprint);
    }

    public function test_scanner_discovers_multi_file_game_with_nested_assets(): void
    {
        $gameDir = $this->testStagingPath.'/space-odyssey';
        File::makeDirectory($gameDir.'/assets/js', 0755, true);
        File::makeDirectory($gameDir.'/assets/images', 0755, true);

        File::put($gameDir.'/index.html', '<!DOCTYPE html><html><head><title>Space Odyssey</title></head><body><script src="assets/js/engine.js"></script></body></html>');
        File::put($gameDir.'/assets/js/engine.js', 'console.log("engine loaded");');
        File::put($gameDir.'/assets/images/background.png', 'fake-png-data');

        /** @var GameScannerService $scanner */
        $scanner = app(GameScannerService::class);
        $result = $scanner->scan();

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['discovered']);
        $this->assertEquals(1, $result['drafts_created']);

        $draft = Game::where('slug', 'space-odyssey')->first();
        $this->assertNotNull($draft);
        $this->assertEquals('multi_file', $draft->game_type);
        $this->assertEquals(3, $draft->scan_metadata['file_count']);
    }

    public function test_scanner_rejects_game_missing_entry_html_file(): void
    {
        $gameDir = $this->testStagingPath.'/no-entry-game';
        File::makeDirectory($gameDir.'/assets', 0755, true);
        File::put($gameDir.'/assets/script.js', 'console.log("no html");');

        /** @var GameScannerService $scanner */
        $scanner = app(GameScannerService::class);
        $result = $scanner->scan();

        $this->assertEquals(1, $result['discovered']);
        $this->assertEquals(0, $result['drafts_created']);
        $this->assertEquals(1, $result['rejected']);
        $this->assertStringContainsString('Entry HTML file not found', $result['results'][0]['reason']);

        $this->assertDatabaseMissing('games', ['slug' => 'no-entry-game']);
    }

    public function test_scanner_prioritizes_index_html_when_multiple_candidates_exist(): void
    {
        $gameDir = $this->testStagingPath.'/multi-entry-game';
        File::makeDirectory($gameDir, 0755, true);
        File::put($gameDir.'/game.html', '<html><head><title>Game HTML</title></head></html>');
        File::put($gameDir.'/index.html', '<html><head><title>Index HTML</title></head></html>');

        /** @var GameScannerService $scanner */
        $scanner = app(GameScannerService::class);
        $result = $scanner->scan();

        $this->assertEquals(1, $result['drafts_created']);
        $draft = Game::where('slug', 'index-html')->first();
        $this->assertNotNull($draft);
        $this->assertEquals('index.html', $draft->entry_file);
    }

    public function test_scanner_artisan_command_executes_successfully(): void
    {
        $gameDir = $this->testStagingPath.'/cli-runner';
        File::makeDirectory($gameDir, 0755, true);
        File::put($gameDir.'/index.html', '<!DOCTYPE html><html><head><title>CLI Runner</title></head><body></body></html>');

        $this->artisan('games:scan')
            ->expectsOutputToContain('TapH5 Game Discovery Scanner')
            ->assertExitCode(0);

        $this->assertDatabaseHas('games', [
            'title' => 'CLI Runner',
            'status' => 'draft',
        ]);
    }
}
