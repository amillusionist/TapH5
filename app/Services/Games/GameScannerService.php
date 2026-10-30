<?php

namespace App\Services\Games;

use App\Models\Game;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GameScannerService
{
    public function __construct(
        protected GameValidatorService $validator,
        protected GameFingerprintService $fingerprintService,
        protected GameMetadataExtractor $metadataExtractor,
        protected GameThumbnailService $thumbnailService,
        protected GameCategorySuggestionService $categorySuggester,
    ) {}

    /**
     * Run discovery scan on the staging directory.
     *
     * @param  string|null  $targetFolder  Optional single game folder to scan
     * @param  bool  $dryRun  If true, simulates scan without creating/updating database records
     * @return array{
     *     success: bool,
     *     message: string,
     *     staging_root: string,
     *     discovered: int,
     *     drafts_created: int,
     *     drafts_updated: int,
     *     duplicates: int,
     *     rejected: int,
     *     results: list<array<string, mixed>>
     * }
     */
    public function scan(?string $targetFolder = null, bool $dryRun = false): array
    {
        $stagingRootConfig = (string) Config::get('games.staging_root', storage_path('staging'));
        $stagingRoot = $this->resolvePath($stagingRootConfig);

        if (! is_dir($stagingRoot)) {
            // Attempt to create directory if in local/testing environment
            if (! @mkdir($stagingRoot, 0755, true) && ! is_dir($stagingRoot)) {
                return [
                    'success' => false,
                    'message' => "Staging directory does not exist or is not readable: [{$stagingRootConfig}].",
                    'staging_root' => $stagingRootConfig,
                    'discovered' => 0,
                    'drafts_created' => 0,
                    'drafts_updated' => 0,
                    'duplicates' => 0,
                    'rejected' => 0,
                    'results' => [],
                ];
            }
        }

        $realStagingRoot = realpath($stagingRoot);
        if ($realStagingRoot === false || ! is_readable($realStagingRoot)) {
            return [
                'success' => false,
                'message' => "Staging directory cannot be read: [{$stagingRootConfig}]. Check filesystem permissions.",
                'staging_root' => $stagingRootConfig,
                'discovered' => 0,
                'drafts_created' => 0,
                'drafts_updated' => 0,
                'duplicates' => 0,
                'rejected' => 0,
                'results' => [],
            ];
        }

        // Enumerate candidate game directories
        $folders = [];
        if ($targetFolder !== null) {
            $targetClean = basename($targetFolder);
            if (is_dir($realStagingRoot.DIRECTORY_SEPARATOR.$targetClean)) {
                $folders[] = $targetClean;
            } else {
                return [
                    'success' => false,
                    'message' => "Specified target game folder not found in staging: '{$targetClean}'.",
                    'staging_root' => $realStagingRoot,
                    'discovered' => 0,
                    'drafts_created' => 0,
                    'drafts_updated' => 0,
                    'duplicates' => 0,
                    'rejected' => 0,
                    'results' => [],
                ];
            }
        } else {
            $items = scandir($realStagingRoot) ?: [];
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                if (is_dir($realStagingRoot.DIRECTORY_SEPARATOR.$item)) {
                    $folders[] = $item;
                }
            }
        }

        sort($folders);

        $results = [];
        $draftsCreated = 0;
        $draftsUpdated = 0;
        $duplicates = 0;
        $rejected = 0;

        foreach ($folders as $folder) {
            $folderFullPath = $realStagingRoot.DIRECTORY_SEPARATOR.$folder;

            // 1. Validation
            $validation = $this->validator->validate($realStagingRoot, $folder);
            if (! $validation['is_valid']) {
                $rejected++;
                $results[] = [
                    'folder' => $folder,
                    'status' => 'rejected',
                    'reason' => implode('; ', $validation['errors']),
                    'warnings' => $validation['warnings'],
                ];

                continue;
            }

            // 2. Fingerprint
            $fingerprint = $this->fingerprintService->computeDirectoryFingerprint($folderFullPath);
            $stagingRelativePath = "staging/{$folder}";

            // 3. Metadata Extraction
            $entryFilePath = $folderFullPath.DIRECTORY_SEPARATOR.$validation['entry_file'];
            $metadata = $this->metadataExtractor->extract($entryFilePath, $folder);

            // 4. Duplicate Check
            $dupCheck = $this->fingerprintService->checkDuplicate(
                $fingerprint,
                $metadata['slug'],
                $metadata['title'],
                $stagingRelativePath
            );

            if ($dupCheck['is_duplicate'] && $dupCheck['type'] === 'exact_fingerprint') {
                $duplicates++;
                $results[] = [
                    'folder' => $folder,
                    'title' => $metadata['title'],
                    'status' => 'duplicate',
                    'reason' => $dupCheck['message'],
                    'fingerprint' => $fingerprint,
                    'existing_game_id' => $dupCheck['existing_game']?->id,
                ];

                continue;
            }

            // 5. Category Suggestion
            $categorySuggestion = $this->categorySuggester->suggest(
                $metadata['title'],
                $folder,
                $metadata['description']
            );

            // 6. Thumbnail Resolution
            $thumbnailResult = $this->thumbnailService->resolveAndStoreThumbnail(
                $folderFullPath,
                $metadata['slug'],
                $metadata
            );

            $allWarnings = array_merge(
                $validation['warnings'],
                $metadata['warnings'],
                $thumbnailResult['warnings']
            );

            if ($dupCheck['type'] === 'slug_conflict' || $dupCheck['type'] === 'title_similarity_warning') {
                $allWarnings[] = $dupCheck['message'];
            }

            // Ensure unique slug for new records if conflict exists
            $finalSlug = $metadata['slug'];
            $isSameDraft = false;
            $existingGame = null;

            if ($dupCheck['type'] === 'same_existing_record' && $dupCheck['existing_game'] !== null) {
                $existingGame = $dupCheck['existing_game'];
                $isSameDraft = $existingGame->status === 'draft';
            } else {
                $existingGame = Game::where('slug', $finalSlug)->first();
                $isSameDraft = $existingGame !== null
                    && $existingGame->status === 'draft'
                    && ($existingGame->storage_path === $stagingRelativePath || $existingGame->fingerprint === $fingerprint);
            }

            if ($existingGame !== null && ! $isSameDraft) {
                // Slug conflict with another published or different game: append suffix
                $finalSlug = "{$metadata['slug']}-".Str::random(5);
                $allWarnings[] = "Slug conflict resolved: unique slug generated as '{$finalSlug}'.";
            }

            $scanMetadataPayload = [
                'folder' => $folder,
                'entry_file' => $validation['entry_file'],
                'game_type' => $validation['game_type'],
                'file_count' => $validation['file_count'],
                'total_size_bytes' => $validation['total_size'],
                'fingerprint' => $fingerprint,
                'scanned_at' => Carbon::now()->toIso8601String(),
                'category_suggestion' => [
                    'category_id' => $categorySuggestion['category']?->id,
                    'category_name' => $categorySuggestion['category']?->name,
                    'confidence' => $categorySuggestion['confidence'],
                    'matched_keywords' => $categorySuggestion['matched_keywords'],
                ],
                'thumbnail_detected' => $thumbnailResult['found'],
                'thumbnail_source' => $thumbnailResult['source_file'],
                'warnings' => $allWarnings,
            ];

            if ($dryRun) {
                $results[] = [
                    'folder' => $folder,
                    'title' => $metadata['title'],
                    'slug' => $finalSlug,
                    'status' => $isSameDraft ? 'would_update_draft' : 'would_create_draft',
                    'entry_file' => $validation['entry_file'],
                    'game_type' => $validation['game_type'],
                    'files' => $validation['file_count'],
                    'size' => $validation['total_size'],
                    'thumbnail' => $thumbnailResult['thumbnail_url'],
                    'category' => $categorySuggestion['category']?->name ?? 'Unassigned',
                    'warnings' => $allWarnings,
                ];
                if ($isSameDraft) {
                    $draftsUpdated++;
                } else {
                    $draftsCreated++;
                }

                continue;
            }

            // 7. Persist or update Draft record safely in database
            DB::transaction(function () use (
                $isSameDraft,
                $existingGame,
                $metadata,
                $finalSlug,
                $thumbnailResult,
                $categorySuggestion,
                $validation,
                $stagingRelativePath,
                $fingerprint,
                $scanMetadataPayload,
                &$draftsCreated,
                &$draftsUpdated
            ) {
                if ($isSameDraft && $existingGame !== null) {
                    // Update only scan-related properties, preserve any manual edits to title/description
                    $existingGame->update([
                        'fingerprint' => $fingerprint,
                        'entry_file' => $validation['entry_file'],
                        'game_type' => $validation['game_type'],
                        'thumbnail' => $existingGame->thumbnail ?: $thumbnailResult['thumbnail_url'],
                        'category_id' => $existingGame->category_id ?: $categorySuggestion['category']?->id,
                        'scan_metadata' => $scanMetadataPayload,
                    ]);
                    $draftsUpdated++;
                } else {
                    Game::create([
                        'title' => $metadata['title'],
                        'slug' => $finalSlug,
                        'description' => $metadata['description'],
                        'instructions' => 'Use keyboard arrows or touch controls to play.',
                        'thumbnail' => $thumbnailResult['thumbnail_url'],
                        'category_id' => $categorySuggestion['category']?->id,
                        'game_type' => $validation['game_type'],
                        'entry_file' => $validation['entry_file'],
                        'storage_path' => $stagingRelativePath,
                        'fingerprint' => $fingerprint,
                        'scan_metadata' => $scanMetadataPayload,
                        'status' => 'draft',
                        'is_featured' => false,
                        'is_trending' => false,
                        'is_popular' => false,
                        'plays_count' => 0,
                        'seo_title' => $metadata['seo_title'],
                        'seo_description' => $metadata['seo_description'],
                    ]);
                    $draftsCreated++;
                }
            });

            $results[] = [
                'folder' => $folder,
                'title' => $metadata['title'],
                'slug' => $finalSlug,
                'status' => $isSameDraft ? 'draft_updated' : 'draft_created',
                'entry_file' => $validation['entry_file'],
                'game_type' => $validation['game_type'],
                'files' => $validation['file_count'],
                'size' => $validation['total_size'],
                'thumbnail' => $thumbnailResult['thumbnail_url'],
                'category' => $categorySuggestion['category']?->name ?? 'Unassigned',
                'warnings' => $allWarnings,
            ];
        }

        return [
            'success' => true,
            'message' => 'Scan completed successfully.',
            'staging_root' => $realStagingRoot,
            'discovered' => count($folders),
            'drafts_created' => $draftsCreated,
            'drafts_updated' => $draftsUpdated,
            'duplicates' => $duplicates,
            'rejected' => $rejected,
            'results' => $results,
        ];
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
