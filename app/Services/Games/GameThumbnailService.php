<?php

namespace App\Services\Games;

use Illuminate\Support\Facades\Config;

class GameThumbnailService
{
    /**
     * Find, validate, and copy a game thumbnail into portal thumbnail storage.
     *
     * @param  string  $gameDirectory  Absolute path to the game directory
     * @param  string  $slug  Game slug used for naming the copied thumbnail
     * @param  array<string, mixed>  $metadata  Extracted HTML metadata
     * @return array{
     *     thumbnail_url: string,
     *     found: bool,
     *     source_file: string|null,
     *     is_fallback: bool,
     *     warnings: list<string>
     * }
     */
    public function resolveAndStoreThumbnail(string $gameDirectory, string $slug, array $metadata = []): array
    {
        $warnings = [];
        $defaultThumbnail = (string) Config::get('games.default_thumbnail', '/images/thumbnails/default-game.svg');
        $allowedImageExtensions = ['png', 'jpg', 'jpeg', 'webp', 'svg', 'gif'];
        $maxFileSize = (int) Config::get('games.max_file_size', 52428800);

        $realGameDir = realpath($gameDirectory);

        if ($realGameDir === false) {
            return [
                'thumbnail_url' => $defaultThumbnail,
                'found' => false,
                'source_file' => null,
                'is_fallback' => true,
                'warnings' => ['Invalid game directory for thumbnail resolution.'],
            ];
        }

        // 1. Compile candidate relative paths in priority order
        $candidates = [];

        if (! empty($metadata['og_image']) && is_string($metadata['og_image'])) {
            $candidates[] = ltrim($metadata['og_image'], '/\\');
        }

        if (! empty($metadata['twitter_image']) && is_string($metadata['twitter_image'])) {
            $candidates[] = ltrim($metadata['twitter_image'], '/\\');
        }

        if (! empty($metadata['icon']) && is_string($metadata['icon'])) {
            $candidates[] = ltrim($metadata['icon'], '/\\');
        }

        $standardNames = (array) Config::get('games.thumbnail_candidates', [
            'thumbnail.png', 'thumbnail.jpg', 'thumbnail.jpeg', 'thumbnail.webp', 'thumbnail.svg',
            'cover.png', 'cover.jpg', 'cover.jpeg', 'cover.webp', 'cover.svg',
            'screenshot.png', 'screenshot.jpg', 'screenshot.jpeg', 'screenshot.webp',
            'preview.png', 'preview.jpg', 'preview.jpeg', 'preview.webp',
            'poster.png', 'poster.jpg', 'poster.jpeg', 'poster.webp',
            'banner.png', 'banner.jpg', 'banner.jpeg', 'banner.webp',
            'thumb.png', 'thumb.jpg', 'thumb.jpeg', 'thumb.webp',
            'icon.png', 'icon.jpg', 'icon.jpeg', 'icon.webp', 'icon.svg',
            'logo.png', 'logo.jpg', 'logo.svg', 'logo.webp',
            'icon-512.png', 'icon-192.png',
        ]);

        $subdirs = ['', 'assets/', 'images/', 'img/', 'media/', 'icons/'];
        foreach ($standardNames as $name) {
            foreach ($subdirs as $subdir) {
                $candidates[] = $subdir.$name;
            }
        }

        $foundSourceFile = null;

        foreach ($candidates as $candidateRel) {
            // Reject any traversal in candidate path
            if (str_contains($candidateRel, '..')) {
                continue;
            }

            $candidateRelClean = str_replace('\\', '/', $candidateRel);
            $fullCandidatePath = $realGameDir.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $candidateRelClean);

            if (! is_file($fullCandidatePath)) {
                continue;
            }

            $realCandidatePath = realpath($fullCandidatePath);
            if ($realCandidatePath === false) {
                continue;
            }

            // Verify canonical path is strictly inside the game directory
            if (! str_starts_with($realCandidatePath, $realGameDir.DIRECTORY_SEPARATOR)) {
                continue;
            }

            // Verify extension
            $ext = strtolower(pathinfo($realCandidatePath, PATHINFO_EXTENSION));
            if (! in_array($ext, $allowedImageExtensions, true)) {
                continue;
            }

            // Verify file size
            if (filesize($realCandidatePath) > $maxFileSize) {
                continue;
            }

            $foundSourceFile = $realCandidatePath;
            break;
        }

        // 2. Heuristic check: look for any root image file if candidates yielded nothing
        if ($foundSourceFile === null) {
            $rootFiles = @scandir($realGameDir) ?: [];
            foreach ($rootFiles as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($ext, $allowedImageExtensions, true)) {
                    $checkPath = $realGameDir.DIRECTORY_SEPARATOR.$file;
                    if (is_file($checkPath) && filesize($checkPath) <= $maxFileSize) {
                        $foundSourceFile = realpath($checkPath);
                        break;
                    }
                }
            }
        }

        // 3. If source file was found inside game directory, copy it to public thumbnail storage
        if ($foundSourceFile !== null) {
            $ext = strtolower(pathinfo($foundSourceFile, PATHINFO_EXTENSION));
            $destinationDir = public_path('images/thumbnails');

            if (! is_dir($destinationDir)) {
                @mkdir($destinationDir, 0755, true);
            }

            $destinationFileName = "{$slug}.{$ext}";
            $destinationFilePath = $destinationDir.DIRECTORY_SEPARATOR.$destinationFileName;

            if (@copy($foundSourceFile, $destinationFilePath)) {
                return [
                    'thumbnail_url' => "/images/thumbnails/{$destinationFileName}",
                    'found' => true,
                    'source_file' => str_replace('\\', '/', substr($foundSourceFile, strlen($realGameDir) + 1)),
                    'is_fallback' => false,
                    'warnings' => [],
                ];
            }

            $warnings[] = "Failed to copy thumbnail '{$foundSourceFile}' to portal thumbnail storage.";
        }

        // 4. Check if portal already has a dedicated thumbnail created for this slug (e.g. classic-snake-taph5.svg)
        $publicThumbDir = public_path('images/thumbnails');
        if (is_dir($publicThumbDir)) {
            foreach ($allowedImageExtensions as $ext) {
                $existingThumb = $publicThumbDir.DIRECTORY_SEPARATOR."{$slug}.{$ext}";
                if (is_file($existingThumb)) {
                    return [
                        'thumbnail_url' => "/images/thumbnails/{$slug}.{$ext}",
                        'found' => true,
                        'source_file' => "portal:images/thumbnails/{$slug}.{$ext}",
                        'is_fallback' => false,
                        'warnings' => [],
                    ];
                }
            }
        }

        // 5. Fallback cleanly to default thumbnail with review warning
        $warnings[] = 'No valid thumbnail found in game directory; using portal default thumbnail.';

        return [
            'thumbnail_url' => $defaultThumbnail,
            'found' => false,
            'source_file' => null,
            'is_fallback' => true,
            'warnings' => $warnings,
        ];
    }
}
