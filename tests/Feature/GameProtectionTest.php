<?php

namespace Tests\Feature;

use App\Services\Games\GameScannerService;
use App\Services\Games\GameValidatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class GameProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected string $testStagingPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testStagingPath = storage_path('framework/testing/prot_staging_'.uniqid());
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

    public function test_validator_rejects_path_traversal_attempts(): void
    {
        /** @var GameValidatorService $validator */
        $validator = app(GameValidatorService::class);

        $result1 = $validator->validate($this->testStagingPath, '../outside');
        $this->assertFalse($result1['is_valid']);
        $this->assertStringContainsString('Invalid or unsafe', $result1['errors'][0]);

        $result2 = $validator->validate($this->testStagingPath, 'sub/folder');
        $this->assertFalse($result2['is_valid']);
    }

    public function test_validator_rejects_server_executable_files(): void
    {
        $gameDir = $this->testStagingPath.'/forbidden-ext-game';
        File::makeDirectory($gameDir, 0755, true);
        File::put($gameDir.'/index.html', '<html><body>Safe HTML Game</body></html>');
        File::put($gameDir.'/helper.php', '<?php echo "test";');
        File::put($gameDir.'/runner.sh', '#!/bin/sh');

        /** @var GameValidatorService $validator */
        $validator = app(GameValidatorService::class);
        $result = $validator->validate($this->testStagingPath, 'forbidden-ext-game');

        $this->assertFalse($result['is_valid']);
        $errorString = implode(' ', $result['errors']);
        $this->assertStringContainsString('helper.php', $errorString);
        $this->assertStringContainsString('runner.sh', $errorString);

        /** @var GameScannerService $scanner */
        $scanner = app(GameScannerService::class);
        $scanResult = $scanner->scan();
        $this->assertEquals(1, $scanResult['rejected']);
        $this->assertEquals(0, $scanResult['drafts_created']);
    }

    public function test_validator_rejects_individual_file_exceeding_size_limit(): void
    {
        Config::set('games.max_file_size', 100);

        $gameDir = $this->testStagingPath.'/large-file-game';
        File::makeDirectory($gameDir, 0755, true);
        File::put($gameDir.'/index.html', '<html>'.str_repeat('A', 200).'</html>');

        /** @var GameValidatorService $validator */
        $validator = app(GameValidatorService::class);
        $result = $validator->validate($this->testStagingPath, 'large-file-game');

        $this->assertFalse($result['is_valid']);
        $this->assertStringContainsString('exceeds maximum allowed size', $result['errors'][0]);
    }

    public function test_validator_rejects_total_file_count_exceeding_limit(): void
    {
        Config::set('games.max_files', 3);

        $gameDir = $this->testStagingPath.'/many-files-game';
        File::makeDirectory($gameDir, 0755, true);
        File::put($gameDir.'/index.html', '<html></html>');
        File::put($gameDir.'/style1.css', 'body{}');
        File::put($gameDir.'/style2.css', 'body{}');
        File::put($gameDir.'/style3.css', 'body{}');

        /** @var GameValidatorService $validator */
        $validator = app(GameValidatorService::class);
        $result = $validator->validate($this->testStagingPath, 'many-files-game');

        $this->assertFalse($result['is_valid']);
        $this->assertStringContainsString('Total file count (4) exceeds maximum allowed limit', $result['errors'][0]);
    }
}
