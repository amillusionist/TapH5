<?php

namespace App\Services\Games;

use App\Models\Game;
use App\Models\GameReview;
use App\Services\Admin\AdminAuditService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

class GameOperationsService
{
    public function __construct(
        protected GameValidatorService $validator,
        protected GameFingerprintService $fingerprintService,
        protected GameMetadataExtractor $metadataExtractor,
        protected GameThumbnailService $thumbnailService,
        protected GamePublisherService $publisherService,
    ) {}

    /**
     * Rescan game directory, updating file counts, sizes, fingerprint and scan metadata.
     *
     * @return array{success: bool, message: string, game: Game}
     */
    public function rescan(Game $game): array
    {
        $dir = $this->resolveGameDirectory($game);
        if ($dir === null || ! is_dir($dir)) {
            return [
                'success' => false,
                'message' => "Physical directory for game '{$game->title}' not found.",
                'game' => $game,
            ];
        }

        $folder = basename($dir);
        $parent = dirname($dir);
        $validation = $this->validator->validate($parent, $folder);
        $fingerprint = $this->fingerprintService->computeDirectoryFingerprint($dir);

        $scanMeta = $game->scan_metadata ?? [];
        $scanMeta['rescanned_at'] = Carbon::now()->toIso8601String();
        $scanMeta['file_count'] = $validation['file_count'];
        $scanMeta['total_size_bytes'] = $validation['total_size'];
        $scanMeta['fingerprint'] = $fingerprint;
        $scanMeta['is_valid'] = $validation['is_valid'];
        $scanMeta['validation_errors'] = $validation['errors'];
        $scanMeta['validation_warnings'] = $validation['warnings'];

        $game->update([
            'fingerprint' => $fingerprint ?: $game->fingerprint,
            'entry_file' => $validation['entry_file'] ?: $game->entry_file,
            'game_type' => $validation['game_type'] ?: $game->game_type,
            'scan_metadata' => $scanMeta,
        ]);

        GameReview::create([
            'game_id' => $game->id,
            'user_id' => auth()->id(),
            'action' => GameReview::ACTION_RESCANNED,
            'notes' => "Rescanned game files: {$validation['file_count']} files, {$validation['total_size']} bytes.",
            'metadata' => [
                'file_count' => $validation['file_count'],
                'total_size' => $validation['total_size'],
                'is_valid' => $validation['is_valid'],
            ],
        ]);

        AdminAuditService::log(
            'game.rescanned',
            'Game',
            (string) $game->id,
            "Rescanned files for game '{$game->title}'",
            ['file_count' => $validation['file_count'], 'size' => $validation['total_size']]
        );

        return [
            'success' => true,
            'message' => "Game '{$game->title}' rescanned successfully ({$validation['file_count']} files verified).",
            'game' => $game->fresh(),
        ];
    }

    /**
     * Run validation suite on the game's actual physical files.
     *
     * @return array<string, mixed>
     */
    public function revalidate(Game $game): array
    {
        $dir = $this->resolveGameDirectory($game);
        if ($dir === null || ! is_dir($dir)) {
            return [
                'is_valid' => false,
                'errors' => ["Game directory cannot be located on disk: [{$game->storage_path}]."],
                'warnings' => [],
                'file_count' => 0,
                'total_size' => 0,
            ];
        }

        $folder = basename($dir);
        $parent = dirname($dir);

        return $this->validator->validate($parent, $folder);
    }

    /**
     * Regenerate game thumbnail by scanning candidates or extracted og:image.
     *
     * @return array{success: bool, message: string, thumbnail_url: string}
     */
    public function regenerateThumbnail(Game $game): array
    {
        $dir = $this->resolveGameDirectory($game);
        if ($dir === null || ! is_dir($dir)) {
            return [
                'success' => false,
                'message' => 'Game directory not found for thumbnail regeneration.',
                'thumbnail_url' => $game->thumbnail ?? '',
            ];
        }

        $entryFilePath = $dir.DIRECTORY_SEPARATOR.$game->entry_file;
        $folder = basename($dir);
        $meta = is_file($entryFilePath) ? $this->metadataExtractor->extract($entryFilePath, $folder) : [];

        $res = $this->thumbnailService->resolveAndStoreThumbnail($dir, $game->slug, $meta);
        if (! empty($res['thumbnail_url'])) {
            $game->update(['thumbnail' => $res['thumbnail_url']]);

            GameReview::create([
                'game_id' => $game->id,
                'user_id' => auth()->id(),
                'action' => GameReview::ACTION_THUMBNAIL_REGENERATED,
                'notes' => "Regenerated thumbnail: {$res['thumbnail_url']}",
                'metadata' => $res,
            ]);

            AdminAuditService::log(
                'game.thumbnail_regenerated',
                'Game',
                (string) $game->id,
                "Regenerated thumbnail for game '{$game->title}'",
                $res
            );

            return [
                'success' => true,
                'message' => "Thumbnail regenerated successfully for '{$game->title}'.",
                'thumbnail_url' => $res['thumbnail_url'],
            ];
        }

        return [
            'success' => false,
            'message' => 'No valid thumbnail candidate found in game package.',
            'thumbnail_url' => $game->thumbnail ?? '',
        ];
    }

    /**
     * Refresh metadata (description, instructions) from entry file.
     *
     * @return array{success: bool, message: string, game: Game}
     */
    public function refreshMetadata(Game $game): array
    {
        $dir = $this->resolveGameDirectory($game);
        if ($dir === null || ! is_dir($dir)) {
            return [
                'success' => false,
                'message' => 'Game directory not found for metadata extraction.',
                'game' => $game,
            ];
        }

        $entryFilePath = $dir.DIRECTORY_SEPARATOR.$game->entry_file;
        if (! is_file($entryFilePath)) {
            return [
                'success' => false,
                'message' => "Entry file '{$game->entry_file}' not found.",
                'game' => $game,
            ];
        }

        $meta = $this->metadataExtractor->extract($entryFilePath, basename($dir));

        $updateData = [];
        if (! empty($meta['description']) && empty($game->description)) {
            $updateData['description'] = $meta['description'];
        }
        if (! empty($meta['instructions']) && empty($game->instructions)) {
            $updateData['instructions'] = $meta['instructions'];
        }
        if (! empty($meta['seo_title']) && empty($game->seo_title)) {
            $updateData['seo_title'] = $meta['seo_title'];
        }
        if (! empty($meta['seo_description']) && empty($game->seo_description)) {
            $updateData['seo_description'] = $meta['seo_description'];
        }

        if (! empty($updateData)) {
            $game->update($updateData);

            GameReview::create([
                'game_id' => $game->id,
                'user_id' => auth()->id(),
                'action' => GameReview::ACTION_METADATA_REFRESHED,
                'notes' => 'Refreshed metadata from package HTML.',
                'metadata' => $updateData,
            ]);

            AdminAuditService::log(
                'game.metadata_refreshed',
                'Game',
                (string) $game->id,
                "Refreshed metadata for game '{$game->title}'",
                $updateData
            );
        }

        return [
            'success' => true,
            'message' => "Metadata refreshed for '{$game->title}'.",
            'game' => $game->fresh(),
        ];
    }

    /**
     * Safely replace game package with updated files from staging with atomic rollback.
     *
     * @return array{success: bool, message: string}
     */
    public function replacePackage(Game $game, string $stagingFolder): array
    {
        $cleanFolder = basename(trim($stagingFolder));
        if ($cleanFolder !== $stagingFolder || str_contains($stagingFolder, '..') || blank($cleanFolder)) {
            return [
                'success' => false,
                'message' => 'Invalid staging folder name specified.',
            ];
        }

        $lock = Cache::lock("game_package_replace_{$game->id}", 30);
        if (! $lock->get()) {
            return [
                'success' => false,
                'message' => "Another package replacement operation is currently in progress for game #{$game->id}.",
            ];
        }

        try {
            $stagingRoot = realpath(Config::get('games.staging_root', storage_path('staging')));
            if ($stagingRoot === false || ! is_dir($stagingRoot.DIRECTORY_SEPARATOR.$cleanFolder)) {
                return [
                    'success' => false,
                    'message' => "Staging folder '{$cleanFolder}' does not exist.",
                ];
            }

            $sourceFullPath = $stagingRoot.DIRECTORY_SEPARATOR.$cleanFolder;

            // 1. Validate the candidate package
            $validation = $this->validator->validate($stagingRoot, $cleanFolder);
            if (! $validation['is_valid']) {
                return [
                    'success' => false,
                    'message' => 'Replacement package validation failed: '.implode('; ', $validation['errors']),
                ];
            }

            $newFingerprint = $this->fingerprintService->computeDirectoryFingerprint($sourceFullPath);

            // 2. Handle replacement based on publication state
            if ($game->status === 'published') {
                $destDir = $this->resolveGameDirectory($game);
                if ($destDir === null || ! is_dir($destDir)) {
                    return [
                        'success' => false,
                        'message' => 'Target published game directory cannot be located.',
                    ];
                }

                // Create temporary backup directory
                $backupDir = storage_path('app/temp_backups/game_'.uniqid((string) $game->id.'_'));
                File::ensureDirectoryExists($backupDir);

                try {
                    // Copy current files to backup
                    File::copyDirectory($destDir, $backupDir);

                    // Clean destination and copy new files
                    File::cleanDirectory($destDir);
                    File::copyDirectory($sourceFullPath, $destDir);

                    // Verify entry file exists in destination
                    $destEntry = $destDir.DIRECTORY_SEPARATOR.$validation['entry_file'];
                    if (! is_file($destEntry)) {
                        throw new RuntimeException("Entry file '{$validation['entry_file']}' missing after copy.");
                    }

                    // Update database record inside transaction
                    DB::transaction(function () use ($game, $cleanFolder, $newFingerprint, $validation) {
                        $scanMeta = $game->scan_metadata ?? [];
                        $scanMeta['package_replaced_at'] = Carbon::now()->toIso8601String();
                        $scanMeta['replaced_from_staging'] = $cleanFolder;
                        $scanMeta['fingerprint'] = $newFingerprint;
                        $scanMeta['file_count'] = $validation['file_count'];
                        $scanMeta['total_size_bytes'] = $validation['total_size'];

                        $game->update([
                            'fingerprint' => $newFingerprint,
                            'entry_file' => $validation['entry_file'],
                            'game_type' => $validation['game_type'],
                            'scan_metadata' => $scanMeta,
                        ]);

                        GameReview::create([
                            'game_id' => $game->id,
                            'user_id' => auth()->id(),
                            'action' => GameReview::ACTION_PACKAGE_REPLACED,
                            'notes' => "Replaced game package with staging folder '{$cleanFolder}'.",
                            'metadata' => [
                                'staging_folder' => $cleanFolder,
                                'file_count' => $validation['file_count'],
                                'size' => $validation['total_size'],
                                'fingerprint' => $newFingerprint,
                            ],
                        ]);
                    });

                    // Success - clean temporary backup
                    File::deleteDirectory($backupDir);
                } catch (\Throwable $e) {
                    // Rollback files from backup
                    if (is_dir($backupDir)) {
                        File::cleanDirectory($destDir);
                        File::copyDirectory($backupDir, $destDir);
                        File::deleteDirectory($backupDir);
                    }

                    return [
                        'success' => false,
                        'message' => 'Replacement failed with rollback applied: '.$e->getMessage(),
                    ];
                }
            } else {
                // For draft games, update storage path pointer to new staging folder
                DB::transaction(function () use ($game, $cleanFolder, $newFingerprint, $validation) {
                    $scanMeta = $game->scan_metadata ?? [];
                    $scanMeta['package_replaced_at'] = Carbon::now()->toIso8601String();
                    $scanMeta['replaced_from_staging'] = $cleanFolder;
                    $scanMeta['fingerprint'] = $newFingerprint;
                    $scanMeta['file_count'] = $validation['file_count'];
                    $scanMeta['total_size_bytes'] = $validation['total_size'];

                    $game->update([
                        'storage_path' => "staging/{$cleanFolder}",
                        'fingerprint' => $newFingerprint,
                        'entry_file' => $validation['entry_file'],
                        'game_type' => $validation['game_type'],
                        'scan_metadata' => $scanMeta,
                    ]);

                    GameReview::create([
                        'game_id' => $game->id,
                        'user_id' => auth()->id(),
                        'action' => GameReview::ACTION_PACKAGE_REPLACED,
                        'notes' => "Replaced draft game package with staging folder '{$cleanFolder}'.",
                        'metadata' => [
                            'staging_folder' => $cleanFolder,
                            'file_count' => $validation['file_count'],
                            'size' => $validation['total_size'],
                            'fingerprint' => $newFingerprint,
                        ],
                    ]);
                });
            }

            AdminAuditService::log(
                'game.package_replaced',
                'Game',
                (string) $game->id,
                "Replaced game package for '{$game->title}' from staging folder '{$cleanFolder}'",
                ['folder' => $cleanFolder, 'fingerprint' => $newFingerprint]
            );

            return [
                'success' => true,
                'message' => "Game package for '{$game->title}' replaced successfully.",
            ];
        } finally {
            $lock->release();
        }
    }

    /**
     * Inspect duplicate games sharing the same SHA256 fingerprint.
     *
     * @return Collection<int, Game>
     */
    public function findDuplicates(Game $game): Collection
    {
        if (empty($game->fingerprint)) {
            return new Collection;
        }

        return Game::where('fingerprint', $game->fingerprint)
            ->where('id', '!=', $game->id)
            ->get();
    }

    /**
     * Resolve the absolute physical path of a game's folder on disk.
     */
    public function resolveGameDirectory(Game $game): ?string
    {
        $rawPath = (string) $game->storage_path;

        if (str_starts_with($rawPath, 'staging/')) {
            $folder = substr($rawPath, strlen('staging/'));
            $stagingRoot = realpath(Config::get('games.staging_root', storage_path('staging')));
            if ($stagingRoot !== false) {
                $candidate = $stagingRoot.DIRECTORY_SEPARATOR.$folder;
                if (is_dir($candidate)) {
                    return $candidate;
                }
            }
        }

        $publicRoot = realpath(Config::get('games.public_root', public_path('games')));
        if ($publicRoot !== false && ! empty($rawPath)) {
            $cleaned = str_starts_with($rawPath, 'games/') ? substr($rawPath, strlen('games/')) : $rawPath;
            $candidate = $publicRoot.DIRECTORY_SEPARATOR.str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $cleaned);
            if (is_dir($candidate)) {
                return $candidate;
            }
        }

        if (! empty($rawPath) && is_dir($rawPath)) {
            return realpath($rawPath) ?: $rawPath;
        }

        return null;
    }
}
