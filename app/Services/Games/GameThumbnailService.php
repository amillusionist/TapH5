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
     * @return array{thumbnail_url: string|null, found: bool, source_file: string|null, warnings: list<string>}
     */
    public function resolveAndStoreThumbnail(string $gameDirectory, string $slug, array $metadata = []): array
    {
        $warnings = [];
        $realGameDir = realpath($gameDirectory);

        if ($realGameDir === false) {
            return [
                'thumbnail_url' => '/images/thumbnails/neon-pulse.svg',
                'found' => false,
                'source_file' => null,
                'warnings' => ['Invalid game directory for thumbnail resolution.'],
            ];
        }

        $allowedImageExtensions = ['png', 'jpg', 'jpeg', 'webp', 'svg', 'gif'];
        $maxFileSize = (int) Config::get('games.max_file_size', 52428800);

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
            'thumbnail.png', 'thumbnail.jpg', 'thumbnail.jpeg', 'thumbnail.webp',
            'cover.png', 'cover.jpg', 'cover.jpeg', 'cover.webp',
            'thumb.png', 'thumb.jpg', 'icon.png', 'icon.jpg',
        ]);

        foreach ($standardNames as $name) {
            $candidates[] = $name;
            $candidates[] = 'assets/'.$name;
            $candidates[] = 'images/'.$name;
            $candidates[] = 'img/'.$name;
        }

        $foundSourceFile = null;

        foreach ($candidates as $candidateRel) {
            // Reject any traversal in candidate path
            if (str_contains($candidateRel, '..')) {
                continue;
            }

            // Normalize path
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

        if ($foundSourceFile !== null) {
            $ext = strtolower(pathinfo($foundSourceFile, PATHINFO_EXTENSION));
            $destinationDir = public_path('images/thumbnails');

            if (! is_dir($destinationDir)) {
                mkdir($destinationDir, 0755, true);
            }

            $destinationFileName = "{$slug}.{$ext}";
            $destinationFilePath = $destinationDir.DIRECTORY_SEPARATOR.$destinationFileName;

            // Safely copy thumbnail
            if (copy($foundSourceFile, $destinationFilePath)) {
                return [
                    'thumbnail_url' => "/images/thumbnails/{$destinationFileName}",
                    'found' => true,
                    'source_file' => str_replace('\\', '/', substr($foundSourceFile, strlen($realGameDir) + 1)),
                    'warnings' => [],
                ];
            }

            $warnings[] = "Failed to copy thumbnail '{$foundSourceFile}' to portal thumbnail storage.";
        } else {
            $warnings[] = 'No valid thumbnail found in game directory; review required.';
        }

        return [
            'thumbnail_url' => '/images/thumbnails/neon-pulse.svg',
            'found' => false,
            'source_file' => null,
            'warnings' => $warnings,
        ];
    }
}
