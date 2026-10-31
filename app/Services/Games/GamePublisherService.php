<?php

namespace App\Services\Games;

use App\Models\Game;
use App\Services\GameUrlService;
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
        protected ?GameUrlService $urlService = null,
    ) {
        $this->urlService = $urlService ?? app(GameUrlService::class);
    }

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

        // Verify review status is not rejected, pending, or changes_requested
        if (in_array($game->review_status, [Game::REVIEW_REJECTED, Game::REVIEW_CHANGES_REQUESTED, Game::REVIEW_PENDING], true)) {
            return [
                'success' => false,
                'message' => "Game #{$game->id} ('{$game->title}') cannot be published while in '{$game->review_status}' review state.",
                'game' => $game,
                'public_storage_path' => null,
                'public_url' => null,
            ];
        }

        // Security check: reject explicit path traversal in game slug or category slug
        if (str_contains((string) $game->slug, '..') || str_contains((string) $game->category?->slug, '..')) {
            return [
                'success' => false,
                'message' => 'Path traversal detected in game slug or category.',
                'game' => $game,
                'public_storage_path' => null,
                'public_url' => null,
            ];
        }

        // 3. Locate staged source folder
        $stagingRootConfig = (string) Config::get('games.staging_root', storage_path('staging'));
        $stagingRoot = $this->resolvePath($stagingRootConfig);
        $realStagingRoot = realpath($stagingRoot);

        if ($realStagingRoot === false || ! is_dir($realStagingRoot)) {
            return [
                'success' => false,
                'message' => "Staging root directory does not exist: [{$stagingRootConfig}].",
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
        $publicRootConfig = (string) Config::get('games.public_root', public_path('games'));
        $publicRoot = $this->resolvePath($publicRootConfig);
        if (! is_dir($publicRoot)) {
            if (! @mkdir($publicRoot, 0755, true) && ! is_dir($publicRoot)) {
                return [
                    'success' => false,
                    'message' => "Public game storage directory does not exist and cannot be created: [{$publicRootConfig}].",
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
                'message' => "Public game storage root is not writable: [{$publicRootConfig}].",
                'game' => $game,
                'public_storage_path' => null,
                'public_url' => null,
            ];
        }

        // Form sanitized relative path (e.g. 'arcade/neon-pulse' or 'general/neon-pulse')
        // Using 'general' as fallback prevents nested 'games/games/...' directories
        $categoryPrefix = $game->category?->slug ? Str::slug($game->category->slug) : 'general';
        $gameFolderSlug = Str::slug($game->slug);

        if (blank($gameFolderSlug) || $gameFolderSlug === '.' || $gameFolderSlug === '..') {
            return [
                'success' => false,
                'message' => "Invalid game slug for publishing: '{$game->slug}'.",
                'game' => $game,
                'public_storage_path' => null,
                'public_url' => null,
            ];
        }

        if (blank($categoryPrefix) || $categoryPrefix === '.' || $categoryPrefix === '..') {
            $categoryPrefix = 'general';
        }

        $relativePublicPath = "{$categoryPrefix}/{$gameFolderSlug}";
        $destinationFullPath = $realPublicRoot.DIRECTORY_SEPARATOR.$categoryPrefix.DIRECTORY_SEPARATOR.$gameFolderSlug;

        // Canonical confinement check against public games root
        $normalizedDest = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $destinationFullPath);
        $normalizedRoot = rtrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $realPublicRoot), DIRECTORY_SEPARATOR);

        if (! str_starts_with($normalizedDest, $normalizedRoot.DIRECTORY_SEPARATOR)) {
            return [
                'success' => false,
                'message' => "Path traversal detected: destination '{$destinationFullPath}' is outside public root.",
                'game' => $game,
                'public_storage_path' => null,
                'public_url' => null,
            ];
        }

        // Database conflict check: check if this destination belongs to another published game in DB
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

        // Independent filesystem conflict check: detect if destination path already exists on filesystem
        if (file_exists($destinationFullPath) || is_dir($destinationFullPath) || is_link($destinationFullPath)) {
            return [
                'success' => false,
                'message' => "Destination conflict: destination path '{$relativePublicPath}' already exists on the filesystem and cannot be safely overwritten.",
                'game' => $game,
                'public_storage_path' => null,
                'public_url' => null,
            ];
        }

        // 6. Copy source directory to destination tracking created items
        try {
            $copyResult = $this->copyDirectory($sourceFullPath, $destinationFullPath);
        } catch (\Throwable $e) {
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
            $this->rollbackCopied($copyResult['created_files'], $copyResult['created_dirs']);

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

        $publicUrl = $this->urlService->getEmbedUrl($game->fresh());

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
     * Recursively copy directory tree while tracking created files and directories.
     *
     * @return array{created_files: list<string>, created_dirs: list<string>}
     */
    protected function copyDirectory(string $source, string $destination): array
    {
        $createdDirs = [];
        $createdFiles = [];

        try {
            if (! is_dir($destination)) {
                if (! @mkdir($destination, 0755, true) && ! is_dir($destination)) {
                    throw new RuntimeException("Failed to create destination directory: [{$destination}].");
                }
                $createdDirs[] = $destination;
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
                        if (! @mkdir($target, 0755, true) && ! is_dir($target)) {
                            throw new RuntimeException("Failed to create directory: [{$target}].");
                        }
                        $createdDirs[] = $target;
                    }
                } else {
                    $targetDir = dirname($target);
                    if (! is_dir($targetDir)) {
                        if (! @mkdir($targetDir, 0755, true) && ! is_dir($targetDir)) {
                            throw new RuntimeException("Failed to create parent directory: [{$targetDir}].");
                        }
                        $createdDirs[] = $targetDir;
                    }
                    if (! $this->copyFile($item->getRealPath(), $target)) {
                        throw new RuntimeException("Failed to copy '{$item->getRealPath()}' to '{$target}'.");
                    }
                    $createdFiles[] = $target;
                }
            }

            return [
                'created_files' => $createdFiles,
                'created_dirs' => $createdDirs,
            ];
        } catch (\Throwable $e) {
            $this->rollbackCopied($createdFiles, $createdDirs);
            throw $e;
        }
    }

    /**
     * Copy an individual file to target.
     */
    protected function copyFile(string $sourceFile, string $targetFile): bool
    {
        return @copy($sourceFile, $targetFile);
    }

    /**
     * Safely roll back only the files and directories created during this copy operation.
     *
     * @param  list<string>  $createdFiles
     * @param  list<string>  $createdDirs
     */
    protected function rollbackCopied(array $createdFiles, array $createdDirs): void
    {
        foreach (array_reverse($createdFiles) as $file) {
            if (is_file($file) || is_link($file)) {
                @unlink($file);
            }
        }

        foreach (array_reverse($createdDirs) as $dir) {
            if (is_dir($dir)) {
                @rmdir($dir);
            }
        }
    }

    /**
     * Clean up partially created destination on failure.
     * Kept for backwards compatibility if called externally.
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

    /**
     * Resolve absolute filesystem path from potentially relative configuration path.
     */
    protected function resolvePath(string $path): string
    {
        if (Str::startsWith($path, ['/', '\\']) || preg_match('/^[A-Za-z]:[\\\\\/]/', $path)) {
            return $path;
        }

        return base_path($path);
    }
}
