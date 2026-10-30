<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Game;
use App\Services\GameUrlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Tests\TestCase;

class GameUrlServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('games.embed_origin', 'https://taph5gaming.com');
        Config::set('games.url_prefix', 'games');
    }

    public function test_published_game_generates_valid_single_domain_embed_url(): void
    {
        $arcade = Category::factory()->create(['slug' => 'arcade']);
        $game = Game::factory()->create([
            'category_id' => $arcade->id,
            'slug' => 'space-shooter',
            'storage_path' => 'arcade/space-shooter',
            'entry_file' => 'index.html',
            'status' => 'published',
        ]);

        /** @var GameUrlService $urlService */
        $urlService = app(GameUrlService::class);
        $url = $urlService->getEmbedUrl($game);

        $this->assertEquals('https://taph5gaming.com/games/arcade/space-shooter/index.html', $url);
    }

    public function test_fixture_game_preserves_fixture_path_without_double_prefixing(): void
    {
        $game = Game::factory()->create([
            'slug' => 'fixture-demo',
            'storage_path' => 'fixtures/games/neon-pulse',
            'entry_file' => 'index.html',
            'status' => 'published',
        ]);

        /** @var GameUrlService $urlService */
        $urlService = app(GameUrlService::class);
        $url = $urlService->getEmbedUrl($game);

        $this->assertEquals('https://taph5gaming.com/fixtures/games/neon-pulse/index.html', $url);
    }

    public function test_draft_game_does_not_receive_public_embed_url(): void
    {
        $game = Game::factory()->draft()->create([
            'storage_path' => 'staging/unapproved-game',
            'entry_file' => 'index.html',
        ]);

        /** @var GameUrlService $urlService */
        $urlService = app(GameUrlService::class);
        $url = $urlService->getEmbedUrl($game);

        $this->assertEmpty($url);
    }

    public function test_game_url_service_rejects_absolute_url_in_storage_path(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Absolute URLs are not permitted');

        $game = Game::factory()->create([
            'status' => 'published',
            'storage_path' => 'https://external-hacker.com/game',
            'entry_file' => 'index.html',
        ]);

        /** @var GameUrlService $urlService */
        $urlService = app(GameUrlService::class);
        $urlService->getEmbedUrl($game);
    }

    public function test_game_url_service_rejects_path_traversal(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Path traversal detected');

        $game = Game::factory()->create([
            'status' => 'published',
            'storage_path' => 'arcade/../../secret',
            'entry_file' => 'index.html',
        ]);

        /** @var GameUrlService $urlService */
        $urlService = app(GameUrlService::class);
        $urlService->getEmbedUrl($game);
    }

    public function test_game_url_service_sanitizes_and_encodes_path_segments(): void
    {
        $game = Game::factory()->create([
            'status' => 'published',
            'storage_path' => 'arcade/super race game',
            'entry_file' => 'index.html',
        ]);

        /** @var GameUrlService $urlService */
        $urlService = app(GameUrlService::class);
        $url = $urlService->getEmbedUrl($game);

        $this->assertEquals('https://taph5gaming.com/games/arcade/super%20race%20game/index.html', $url);
    }

    public function test_sandbox_attributes_returns_safe_single_domain_attributes(): void
    {
        /** @var GameUrlService $urlService */
        $urlService = app(GameUrlService::class);
        $sandbox = $urlService->getSandboxAttributes();

        $this->assertStringContainsString('allow-scripts', $sandbox);
        $this->assertStringNotContainsString('allow-same-origin', $sandbox);
    }
}
