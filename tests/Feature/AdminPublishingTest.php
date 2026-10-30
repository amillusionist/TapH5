<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AdminPublishingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected string $testStagingPath;

    protected string $testPublicPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();

        $this->testStagingPath = storage_path('framework/testing/admin_pub_staging_'.uniqid());
        $this->testPublicPath = storage_path('framework/testing/admin_pub_public_'.uniqid());

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

    public function test_admin_can_publish_eligible_draft_game(): void
    {
        $arcade = Category::factory()->create(['slug' => 'arcade']);

        // Create staged source files
        $gameDir = $this->testStagingPath.'/space-blaster';
        File::makeDirectory($gameDir, 0755, true);
        File::put($gameDir.'/index.html', '<!DOCTYPE html><html><head><title>Blaster</title></head></html>');

        $draft = Game::factory()->draft()->create([
            'title' => 'Space Blaster',
            'slug' => 'space-blaster',
            'category_id' => $arcade->id,
            'storage_path' => 'staging/space-blaster',
            'entry_file' => 'index.html',
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/games/{$draft->id}/publish");

        $response->assertRedirect(route('admin.games.show', $draft->id));
        $response->assertSessionHas('success');

        $fresh = $draft->fresh();
        $this->assertEquals('published', $fresh->status);
        $this->assertEquals('arcade/space-blaster', $fresh->storage_path);
        $this->assertFileExists($this->testPublicPath.'/arcade/space-blaster/index.html');
    }

    public function test_admin_cannot_publish_non_draft_game(): void
    {
        $game = Game::factory()->create([
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/games/{$game->id}/publish");

        $response->assertSessionHas('error');
        $this->assertEquals('published', $game->fresh()->status);
    }

    public function test_admin_can_archive_game_without_deleting_files(): void
    {
        $arcade = Category::factory()->create(['slug' => 'arcade']);

        // Setup mock published file
        $publicDir = $this->testPublicPath.'/arcade/archivable-game';
        File::makeDirectory($publicDir, 0755, true);
        File::put($publicDir.'/index.html', '<html>Live game</html>');

        $game = Game::factory()->create([
            'title' => 'Archivable Game',
            'slug' => 'archivable-game',
            'category_id' => $arcade->id,
            'status' => 'published',
            'storage_path' => 'arcade/archivable-game',
            'entry_file' => 'index.html',
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/games/{$game->id}/archive");

        $response->assertSessionHas('success');
        $this->assertEquals('archived', $game->fresh()->status);

        // Verify physical file was NOT deleted
        $this->assertFileExists($publicDir.'/index.html');
    }
}

