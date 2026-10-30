<?php

namespace App\Services\Games;

use App\Models\Game;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

class GamePublisherService
{
    public function __construct(
        protected GameValidatorService $validator,
        protected GameFingerprintService $fingerprintService,
    ) {}

    /**
     * Approve and publish a draft game.
     *
     * @param  Game|int|string  $gameIdentifier  Game model, ID, or slug
     * @param  bool  $force  If true, proceeds even if non-fatal warnings exist
     * @return array{
     *     success: bool,
     *     message: string,
     *     game: Game|null,
     *     public_storage_path: string|null,
     *     public_url: string|null
     * }
     */
    public function approveAndPublish(Game|int|string $gameIdentifier, bool $force = false): array
    {
        // 1. Locate game
        $game = $gameIdentifier instanceof Game
            ? $gameIdentifier
            : Game::where('id', $gameIdentifier)->orWhere('slug', $gameIdentifier)->first();

        if ($game === null) {
            return [
                'success' => false,
                'message' => "Game not found for identifier: '{$gameIdentifier}'.",
                'game' => null,
                'public_storage_path' => null,
                'public_url' => null,
            ];
        }

        // 2. Verify status is draft
        if ($game->status !== 'draft') {
            return [
                'success' => false,
                'message' => "Game #{$game->id} ('{$game->title}') cannot be approved: status is '{$game->status}' (expected 'draft').",
                'game' => $game,
                'public_storage_path' => null,
                'public_url' => null,
            ];
        }

        // 3. Locate staged source folder
        $stagingRoot = (string) Config::get('games.staging_root', storage_path('staging'));
        $realStagingRoot = realpath($stagingRoot);

        if ($realStagingRoot === false || ! is_dir($realStagingRoot)) {
            return [
                'success' => false,
                'message' => "Staging root directory does not exist: [{$stagingRoot}].",
                'game' => $game,
                'public_storage_path' => null,
                'public_url' => null,
            ];
        }

        // Extract folder name from storage_path (e.g. 'staging/neon-pulse' => 'neon-pulse')
        $folderName = basename((string) $game->storage_path);
        if (blank($folderName) || $folderName === '.' || $folderName === 'staging') {
            $folderName = $game->slug;
        }

        $sourceFullPath = $realStagingRoot.DIRECTORY_SEPARATOR.$folderName;
        if (! is_dir($sourceFullPath)) {
            return [
                'success' => false,
                'message' => "Staged source directory not found: [{$sourceFullPath}].",
                'game' => $game,
                'public_storage_path' => null,
                'public_url' => null,
            ];
        }

        // 4. Re-validate source directory
        $validation = $this->validator->validate($realStagingRoot, $folderName);
        if (! $validation['is_valid']) {
            return [
                'success' => false,
                'message' => 'Source validation failed before publishing: '.implode('; ', $validation['errors']),
                'game' => $game,
                'public_storage_path' => null,
                'public_url' => null,
            ];
        }

        // 5. Determine public destination path
        $publicRoot = (string) Config::get('games.public_root', public_path('games'));
        if (! is_dir($publicRoot)) {
            if (! @mkdir($publicRoot, 0755, true) && ! is_dir($publicRoot)) {
                return [
                    'success' => false,
                    'message' => "Public game storage directory does not exist and cannot be created: [{$publicRoot}].",
                    'game' => $game,
                    'public_storage_path' => null,
                    'public_url' => null,
                ];
            }
        }

        $realPublicRoot = realpath($publicRoot);
        if ($realPublicRoot === false || ! is_writable($realPublicRoot)) {
            return [
                'success' => false,
                'message' => "Public game storage root is not writable: [{$publicRoot}].",
                'game' => $game,
                'public_storage_path' => null,
                'public_url' => null,
            ];
        }

        // Form sanitized relative path (e.g. 'arcade/neon-pulse' or 'games/neon-pulse')
        $categoryPrefix = $game->category?->slug ? Str::slug($game->category->slug) : 'games';
        $gameFolderSlug = Str::slug($game->slug);
        $relativePublicPath = "{$categoryPrefix}/{$gameFolderSlug}";

        $destinationFullPath = $realPublicRoot.DIRECTORY_SEPARATOR.$categoryPrefix.DIRECTORY_SEPARATOR.$gameFolderSlug;

        // Check for conflicting destination directory
        if (is_dir($destinationFullPath)) {
            // Check if this destination belongs to another published game
            $conflictingGame = Game::where('storage_path', $relativePublicPath)
                ->where('id', '!=', $game->id)
                ->first();

            if ($conflictingGame !== null) {
                return [
                    'success' => false,
                    'message' => "Destination conflict: published game #{$conflictingGame->id} ('{$conflictingGame->title}') already occupies '{$relativePublicPath}'.",
                    'game' => $game,
                    'public_storage_path' => null,
                    'public_url' => null,
                ];
            }
        }

        // 6. Copy source directory to destination
        try {
            $this->copyDirectory($sourceFullPath, $destinationFullPath);
        } catch (\Throwable $e) {
            $this->cleanupDirectory($destinationFullPath);

            return [
                'success' => false,
                'message' => 'Filesystem copy error: '.$e->getMessage(),
                'game' => $game,
                'public_storage_path' => null,
                'public_url' => null,
            ];
        }

        // 7. Verification of published files
        $destEntryFile = $destinationFullPath.DIRECTORY_SEPARATOR.$game->entry_file;
        if (! is_file($destEntryFile)) {
            $this->cleanupDirectory($destinationFullPath);

            return [
                'success' => false,
                'message' => "Verification failed: entry file '{$game->entry_file}' was not found in destination.",
                'game' => $game,
                'public_storage_path' => null,
                'public_url' => null,
            ];
        }

        // Re-compute destination fingerprint
        $publishedFingerprint = $this->fingerprintService->computeDirectoryFingerprint($destinationFullPath);

        // 8. Update database record within transaction
        DB::transaction(function () use ($game, $relativePublicPath, $publishedFingerprint) {
            $scanMeta = $game->scan_metadata ?? [];
            $scanMeta['published_at'] = now()->toIso8601String();
            $scanMeta['published_path'] = $relativePublicPath;

            $game->update([
                'storage_path' => $relativePublicPath,
                'fingerprint' => $publishedFingerprint ?: $game->fingerprint,
                'status' => 'published',
                'scan_metadata' => $scanMeta,
            ]);
        });

        $embedOrigin = rtrim((string) Config::get('games.embed_origin', config('app.url')), '/');
        $publicUrl = "{$embedOrigin}/{$relativePublicPath}/{$game->entry_file}";

        Log::info("Game approved and published: #{$game->id} '{$game->title}' at '{$relativePublicPath}'.");

        return [
            'success' => true,
            'message' => "Game '{$game->title}' successfully approved and published.",
            'game' => $game->fresh(),
            'public_storage_path' => $relativePublicPath,
            'public_url' => $publicUrl,
        ];
    }

    /**
     * Recursively copy directory tree preserving hierarchy.
     */
    protected function copyDirectory(string $source, string $destination): void
    {
        if (! is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        /** @var SplFileInfo $item */
        foreach ($iterator as $item) {
            // Safety: Skip symlinks
            if ($item->isLink()) {
                continue;
            }

            $subPath = $iterator->getSubPathName();
            $target = $destination.DIRECTORY_SEPARATOR.$subPath;

            if ($item->isDir()) {
                if (! is_dir($target)) {
                    mkdir($target, 0755, true);
                }
            } else {
                $targetDir = dirname($target);
                if (! is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                if (! copy($item->getRealPath(), $target)) {
                    throw new RuntimeException("Failed to copy '{$item->getRealPath()}' to '{$target}'.");
                }
            }
        }
    }

    /**
     * Clean up partially created destination on failure.
     */
    protected function cleanupDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                @rmdir($file->getRealPath());
            } else {
                @unlink($file->getRealPath());
            }
        }

        @rmdir($dir);
    }
}
