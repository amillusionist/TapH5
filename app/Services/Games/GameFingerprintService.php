<?php

namespace App\Services\Games;

use App\Models\Game;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class GameFingerprintService
{
    /**
     * Compute a deterministic SHA-256 fingerprint for a directory of files.
     *
     * @param  string  $directoryPath  Absolute path to the game directory
     * @return string 64-character SHA-256 hexadecimal hash
     */
    public function computeDirectoryFingerprint(string $directoryPath): string
    {
        $realDirectory = realpath($directoryPath);
        if ($realDirectory === false || ! is_dir($realDirectory)) {
            return '';
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($realDirectory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isLink() || ! $file->isFile()) {
                continue;
            }

            $realFilePath = $file->getRealPath();
            if ($realFilePath === false) {
                continue;
            }

            // Derive relative path with normalized forward slashes
            $relativePath = str_replace('\\', '/', substr($realFilePath, strlen($realDirectory) + 1));
            $files[$relativePath] = hash_file('sha256', $realFilePath);
        }

        // Sort keys to guarantee deterministic order across OS / filesystems
        ksort($files, SORT_STRING);

        $manifestParts = [];
        foreach ($files as $relPath => $fileHash) {
            $manifestParts[] = "{$relPath}:{$fileHash}";
        }

        return hash('sha256', implode("\n", $manifestParts));
    }

    /**
     * Check if a game with the given attributes already exists in the database.
     *
     * @return array{is_duplicate: bool, type: string|null, existing_game: Game|null, message: string|null}
     */
    public function checkDuplicate(string $fingerprint, string $slug, string $title, ?string $storagePath = null): array
    {
        $sanitizedStorage = ! empty($storagePath) ? trim(str_replace('\\', '/', $storagePath), '/') : null;

        // 1. Exact fingerprint match
        if (! empty($fingerprint)) {
            $matchByFingerprint = Game::where('fingerprint', $fingerprint)->first();
            if ($matchByFingerprint !== null) {
                // If this is the exact same game record being rescanned in the same path, it is not a duplicate
                if ($sanitizedStorage !== null && $matchByFingerprint->storage_path === $sanitizedStorage) {
                    return [
                        'is_duplicate' => false,
                        'type' => 'same_existing_record',
                        'existing_game' => $matchByFingerprint,
                        'message' => 'Same existing record being rescanned.',
                    ];
                }

                return [
                    'is_duplicate' => true,
                    'type' => 'exact_fingerprint',
                    'existing_game' => $matchByFingerprint,
                    'message' => "Exact content duplicate detected (Game ID #{$matchByFingerprint->id}: '{$matchByFingerprint->title}').",
                ];
            }
        }

        // 2. Same storage path match
        if ($sanitizedStorage !== null) {
            $matchByPath = Game::where('storage_path', $sanitizedStorage)->first();
            if ($matchByPath !== null) {
                return [
                    'is_duplicate' => false,
                    'type' => 'same_existing_record',
                    'existing_game' => $matchByPath,
                    'message' => "Same game path '{$sanitizedStorage}' being rescanned.",
                ];
            }
        }

        // 3. Same slug with different fingerprint
        $matchBySlug = Game::where('slug', $slug)->first();
        if ($matchBySlug !== null) {
            return [
                'is_duplicate' => false,
                'type' => 'slug_conflict',
                'existing_game' => $matchBySlug,
                'message' => "Slug conflict: a game with slug '{$slug}' exists with different content. Needs unique slug review.",
            ];
        }

        // 4. Similar title check
        $normalizedTitle = strtolower(trim($title));
        $matchByTitle = Game::whereRaw('LOWER(title) = ?', [$normalizedTitle])->first();
        if ($matchByTitle !== null) {
            return [
                'is_duplicate' => false,
                'type' => 'title_similarity_warning',
                'existing_game' => $matchByTitle,
                'message' => "Notice: An existing game shares a very similar title ('{$matchByTitle->title}').",
            ];
        }

        return [
            'is_duplicate' => false,
            'type' => null,
            'existing_game' => null,
            'message' => null,
        ];
    }
}
