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
     * List all candidate game folders currently residing in the staging root.
     *
     * @return list<array{
     *     folder: string,
     *     file_count: int,
     *     total_size_bytes: int,
     *     total_size_formatted: string,
     *     has_entry_file: bool,
     *     entry_file: string|null,
     *     is_imported: bool,
     *     existing_game: array{id: int, title: string, status: string, review_status: string}|null
     * }>
     */
    public function listStagingFolders(): array
    {
        $stagingRootConfig = (string) Config::get('games.staging_root', storage_path('staging'));
        $stagingRoot = $this->resolvePath($stagingRootConfig);

        if (! is_dir($stagingRoot)) {
            @mkdir($stagingRoot, 0755, true);
        }

        $realStagingRoot = realpath($stagingRoot);
        if ($realStagingRoot === false || ! is_readable($realStagingRoot)) {
            return [];
        }

        $items = scandir($realStagingRoot) ?: [];
        $folders = [];
        $allowedEntryFiles = (array) Config::get('games.allowed_entry_files', ['index.html', 'game.html', 'play.html']);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..' || str_starts_with($item, '.')) {
                continue;
            }

            $fullPath = $realStagingRoot.DIRECTORY_SEPARATOR.$item;
            if (! is_dir($fullPath)) {
                continue;
            }

            // Path traversal safeguard
            if (basename($item) !== $item || str_contains($item, '..')) {
                continue;
            }

            $fileCount = 0;
            $totalSize = 0;
            $detectedEntry = null;

            $files = @scandir($fullPath) ?: [];
            foreach ($files as $f) {
                if ($f === '.' || $f === '..') {
                    continue;
                }
                $fileCount++;
                $fPath = $fullPath.DIRECTORY_SEPARATOR.$f;
                if (is_file($fPath)) {
                    $totalSize += filesize($fPath);
                    if ($detectedEntry === null && in_array(strtolower($f), $allowedEntryFiles, true)) {
                        $detectedEntry = $f;
                    }
                }
            }

            $existingGame = Game::where('storage_path', "staging/{$item}")
                ->orWhere('slug', Str::slug($item))
                ->first();

            $folders[] = [
                'folder' => $item,
                'file_count' => $fileCount,
                'total_size_bytes' => $totalSize,
                'total_size_formatted' => $this->formatBytes($totalSize),
                'has_entry_file' => $detectedEntry !== null,
                'entry_file' => $detectedEntry,
                'is_imported' => $existingGame !== null,
                'existing_game' => $existingGame ? [
                    'id' => $existingGame->id,
                    'title' => $existingGame->title,
                    'status' => $existingGame->status,
                    'review_status' => $existingGame->review_status,
                ] : null,
            ];
        }

        usort($folders, fn ($a, $b) => strcmp($a['folder'], $b['folder']));

        return $folders;
    }

    /**
     * Inspect a specific staging game folder without writing to database.
     *
     * @return array<string, mixed>
     */
    public function inspectStagingFolder(string $folder): array
    {
        $cleanFolder = basename(trim($folder));
        if ($cleanFolder !== $folder || str_contains($folder, '..') || blank($cleanFolder)) {
            return [
                'is_valid' => false,
                'errors' => ['Path traversal attempt or invalid folder name detected.'],
                'warnings' => [],
            ];
        }

        $stagingRootConfig = (string) Config::get('games.staging_root', storage_path('staging'));
        $stagingRoot = $this->resolvePath($stagingRootConfig);
        $realStagingRoot = realpath($stagingRoot);

        if ($realStagingRoot === false || ! is_dir($realStagingRoot.DIRECTORY_SEPARATOR.$cleanFolder)) {
            return [
                'is_valid' => false,
                'errors' => ["Staging folder '{$cleanFolder}' does not exist."],
                'warnings' => [],
            ];
        }

        $folderFullPath = $realStagingRoot.DIRECTORY_SEPARATOR.$cleanFolder;

        // 1. Validate
        $validation = $this->validator->validate($realStagingRoot, $cleanFolder);
        if (! $validation['is_valid']) {
            return [
                'is_valid' => false,
                'folder' => $cleanFolder,
                'errors' => $validation['errors'],
                'warnings' => $validation['warnings'],
            ];
        }

        // 2. Metadata Extraction
        $entryFilePath = $folderFullPath.DIRECTORY_SEPARATOR.$validation['entry_file'];
        $metadata = $this->metadataExtractor->extract($entryFilePath, $cleanFolder);

        // 3. Fingerprint & Duplication Check
        $fingerprint = $this->fingerprintService->computeDirectoryFingerprint($folderFullPath);
        $dupCheck = $this->fingerprintService->checkDuplicate(
            $fingerprint,
            $metadata['slug'],
            $metadata['title'],
            "staging/{$cleanFolder}"
        );

        // 4. Category Suggestion
        $categorySuggestion = $this->categorySuggester->suggest(
            $metadata['title'],
            $cleanFolder,
            $metadata['description']
        );

        // 5. Thumbnail Resolution Preview
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

        return [
            'is_valid' => true,
            'folder' => $cleanFolder,
            'title' => $metadata['title'] ?? null,
            'slug' => $metadata['slug'] ?? null,
            'description' => $metadata['description'] ?? null,
            'instructions' => $metadata['instructions'] ?? null,
            'entry_file' => $validation['entry_file'] ?? 'index.html',
            'game_type' => $validation['game_type'] ?? 'single_file',
            'file_count' => $validation['file_count'] ?? 0,
            'total_size_bytes' => $validation['total_size'] ?? 0,
            'total_size_formatted' => $this->formatBytes($validation['total_size'] ?? 0),
            'fingerprint' => $fingerprint,
            'is_duplicate' => $dupCheck['is_duplicate'] ?? false,
            'duplicate_reason' => $dupCheck['message'] ?? null,
            'existing_game_id' => $dupCheck['existing_game']?->id,
            'suggested_category_id' => $categorySuggestion['category']?->id,
            'suggested_category_name' => $categorySuggestion['category']?->name,
            'category_confidence' => $categorySuggestion['confidence'] ?? null,
            'thumbnail_url' => $thumbnailResult['thumbnail_url'] ?? null,
            'thumbnail_found' => $thumbnailResult['found'] ?? false,
            'warnings' => $allWarnings,
            'errors' => [],
        ];
    }

    /**
     * Format byte count into human-readable representation.
     */
    public function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        }

        return $bytes.' B';
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
