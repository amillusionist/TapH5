<?php

namespace App\Services\Games;

use Illuminate\Support\Facades\Config;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class GameValidatorService
{
    /**
     * Validate a staged game directory.
     *
     * @param  string  $stagingRoot  Absolute path to the staging root
     * @param  string  $gameFolderName  Folder name inside staging root
     * @return array{
     *     is_valid: bool,
     *     errors: list<string>,
     *     warnings: list<string>,
     *     entry_file: string|null,
     *     game_type: string,
     *     file_count: int,
     *     total_size: int,
     *     files: list<string>
     * }
     */
    public function validate(string $stagingRoot, string $gameFolderName): array
    {
        $errors = [];
        $warnings = [];

        // 1. Basic path traversal validation on folder name
        if (str_contains($gameFolderName, '..') || str_contains($gameFolderName, '/') || str_contains($gameFolderName, '\\')) {
            return $this->invalidResult(["Invalid or unsafe game folder name: '{$gameFolderName}'."]);
        }

        $fullPath = rtrim($stagingRoot, '/\\').DIRECTORY_SEPARATOR.$gameFolderName;

        if (! is_dir($fullPath)) {
            return $this->invalidResult(["Game directory does not exist or is not a directory: '{$fullPath}'."]);
        }

        $realFullPath = realpath($fullPath);
        $realStagingRoot = realpath($stagingRoot);

        if ($realFullPath === false || $realStagingRoot === false) {
            return $this->invalidResult(["Unable to resolve canonical filesystem path for '{$gameFolderName}'."]);
        }

        // Verify the resolved canonical directory is strictly inside the staging root
        if (! str_starts_with($realFullPath, $realStagingRoot.DIRECTORY_SEPARATOR)) {
            return $this->invalidResult(['Path traversal detected: directory resolves outside staging root.']);
        }

        $maxFiles = (int) Config::get('games.max_files', 5000);
        $maxFileSize = (int) Config::get('games.max_file_size', 52428800);
        $maxTotalSize = (int) Config::get('games.max_total_size', 524288000);
        $allowedExtensions = array_map('strtolower', (array) Config::get('games.allowed_extensions', []));
        $forbiddenExtensions = array_map('strtolower', (array) Config::get('games.forbidden_extensions', []));
        $allowedEntryFiles = (array) Config::get('games.allowed_entry_files', ['index.html', 'game.html', 'play.html']);

        $fileCount = 0;
        $totalSize = 0;
        $relativeFiles = [];
        $candidateEntryFilesFound = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($realFullPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        /** @var SplFileInfo $item */
        foreach ($iterator as $item) {
            // Reject symbolic links to prevent link attacks and escaping container
            if ($item->isLink()) {
                $errors[] = "Symbolic links are prohibited: '{$item->getFilename()}' is a symlink.";

                continue;
            }

            if ($item->isDir()) {
                continue;
            }

            $fileCount++;
            $size = $item->getSize();
            $totalSize += $size;

            $realFilePath = $item->getRealPath();
            if ($realFilePath === false || ! str_starts_with($realFilePath, $realFullPath)) {
                $errors[] = "File path resolves outside of game directory: '{$item->getFilename()}'.";

                continue;
            }

            $relative = str_replace('\\', '/', substr($realFilePath, strlen($realFullPath) + 1));
            $relativeFiles[] = $relative;

            // Check individual file size limit
            if ($size > $maxFileSize) {
                $errors[] = "File '{$relative}' exceeds maximum allowed size ({$size} > {$maxFileSize} bytes).";
            }

            // Check file extension
            $ext = strtolower($item->getExtension());

            if (in_array($ext, $forbiddenExtensions, true)) {
                $errors[] = "Server-executable or dangerous file rejected: '{$relative}' has extension .{$ext}.";
            } elseif (! empty($allowedExtensions) && ! in_array($ext, $allowedExtensions, true)) {
                // If the file has no extension (e.g. LICENSE or README), permit it as text with a warning, otherwise flag
                if (empty($ext)) {
                    $warnings[] = "File without extension encountered: '{$relative}'.";
                } else {
                    $warnings[] = "Unrecognized file extension .{$ext} on '{$relative}'.";
                }
            }

            // Check for candidate entry files in root level
            if (! str_contains($relative, '/')) {
                foreach ($allowedEntryFiles as $candidate) {
                    if (strcasecmp($relative, $candidate) === 0) {
                        $candidateEntryFilesFound[] = $candidate;
                    }
                }
            }
        }

        // Check total file count limit
        if ($fileCount > $maxFiles) {
            $errors[] = "Total file count ({$fileCount}) exceeds maximum allowed limit of {$maxFiles}.";
        }

        // Check total directory size limit
        if ($totalSize > $maxTotalSize) {
            $errors[] = "Total directory size ({$totalSize} bytes) exceeds maximum limit of {$maxTotalSize} bytes.";
        }

        // Determine entry file
        $selectedEntryFile = null;
        if (! empty($candidateEntryFilesFound)) {
            // Pick based on allowed_entry_files priority
            foreach ($allowedEntryFiles as $entry) {
                if (in_array($entry, $candidateEntryFilesFound, true)) {
                    $selectedEntryFile = $entry;
                    break;
                }
            }

            if (count($candidateEntryFilesFound) > 1) {
                $warnings[] = 'Multiple candidate entry files found: ['.implode(', ', $candidateEntryFilesFound)."]. Selected primary: '{$selectedEntryFile}'.";
            }
        } else {
            $errors[] = 'Entry HTML file not found. Game must contain one of: ['.implode(', ', $allowedEntryFiles).'] at its root.';
        }

        $gameType = ($fileCount === 1 && $selectedEntryFile !== null) ? 'single_file' : 'multi_file';

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'entry_file' => $selectedEntryFile,
            'game_type' => $gameType,
            'file_count' => $fileCount,
            'total_size' => $totalSize,
            'files' => $relativeFiles,
        ];
    }

    /**
     * @param  list<string>  $errors
     * @return array{
     *     is_valid: bool,
     *     errors: list<string>,
     *     warnings: list<string>,
     *     entry_file: string|null,
     *     game_type: string,
     *     file_count: int,
     *     total_size: int,
     *     files: list<string>
     * }
     */
    protected function invalidResult(array $errors): array
    {
        return [
            'is_valid' => false,
            'errors' => $errors,
            'warnings' => [],
            'entry_file' => null,
            'game_type' => 'unknown',
            'file_count' => 0,
            'total_size' => 0,
            'files' => [],
        ];
    }
}
