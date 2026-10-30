<?php

namespace Tests\Feature;

use App\Services\Games\GameMetadataExtractor;
use App\Services\Games\GameThumbnailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class GameMetadataAndThumbnailTest extends TestCase
{
    use RefreshDatabase;

    protected string $testDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testDir = storage_path('framework/testing/meta_test_'.uniqid());
        File::makeDirectory($this->testDir, 0755, true, true);
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->testDir)) {
            File::deleteDirectory($this->testDir);
        }
        parent::tearDown();
    }

    public function test_metadata_extractor_extracts_html_title_description_and_og(): void
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <title>Galactic Racer Deluxe</title>
    <meta name="description" content="An intense interplanetary racing experience.">
    <meta property="og:title" content="OG Galactic Racer">
    <meta property="og:description" content="OG Description of racer.">
    <meta property="og:image" content="assets/og-cover.png">
    <meta name="twitter:image" content="assets/tw-cover.png">
</head>
<body></body>
</html>
HTML;
        File::put($this->testDir.'/index.html', $html);

        /** @var GameMetadataExtractor $extractor */
        $extractor = app(GameMetadataExtractor::class);
        $result = $extractor->extract($this->testDir.'/index.html', 'galactic-racer');

        $this->assertEquals('OG Galactic Racer', $result['title']);
        $this->assertEquals('OG Description of racer.', $result['description']);
        $this->assertEquals('og-galactic-racer', $result['slug']);
        $this->assertEquals('assets/og-cover.png', $result['og_image']);
        $this->assertEquals('assets/tw-cover.png', $result['twitter_image']);
    }

    public function test_metadata_extractor_handles_missing_tags_with_folder_fallback(): void
    {
        File::put($this->testDir.'/index.html', '<html><body>No meta tags here</body></html>');

        /** @var GameMetadataExtractor $extractor */
        $extractor = app(GameMetadataExtractor::class);
        $result = $extractor->extract($this->testDir.'/index.html', 'super-neon-drifter');

        $this->assertEquals('Super Neon Drifter', $result['title']);
        $this->assertEquals('super-neon-drifter', $result['slug']);
        $this->assertStringContainsString('Play Super Neon Drifter online for free', $result['description']);
        $this->assertNotEmpty($result['warnings']);
    }

    public function test_thumbnail_service_finds_and_copies_thumbnail(): void
    {
        File::makeDirectory($this->testDir.'/assets', 0755, true);
        File::put($this->testDir.'/assets/cover.png', 'fake-image-bytes');

        /** @var GameThumbnailService $thumbService */
        $thumbService = app(GameThumbnailService::class);
        $result = $thumbService->resolveAndStoreThumbnail($this->testDir, 'test-game-thumb', [
            'og_image' => 'assets/cover.png',
        ]);

        $this->assertTrue($result['found']);
        $this->assertEquals('assets/cover.png', $result['source_file']);
        $this->assertEquals('/images/thumbnails/test-game-thumb.png', $result['thumbnail_url']);
        $this->assertFileExists(public_path('images/thumbnails/test-game-thumb.png'));

        // Clean up created public test thumbnail
        if (File::exists(public_path('images/thumbnails/test-game-thumb.png'))) {
            File::delete(public_path('images/thumbnails/test-game-thumb.png'));
        }
    }

    public function test_thumbnail_service_falls_back_when_no_image_present(): void
    {
        /** @var GameThumbnailService $thumbService */
        $thumbService = app(GameThumbnailService::class);
        $result = $thumbService->resolveAndStoreThumbnail($this->testDir, 'game-without-thumb', []);

        $this->assertFalse($result['found']);
        $this->assertTrue($result['is_fallback']);
        $this->assertEquals('/images/thumbnails/default-game.svg', $result['thumbnail_url']);
        $this->assertNotEmpty($result['warnings']);
    }
}
