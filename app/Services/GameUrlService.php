<?php

namespace App\Services;

use App\Models\Game;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use InvalidArgumentException;

class GameUrlService
{
    /**
     * Resolve the public embed URL for a given game.
     * Only published games receive a public embed URL.
     */
    public function getEmbedUrl(Game $game): string
    {
        // Enforce published-only policy
        if ($game->status !== 'published') {
            return '';
        }

        $rawStoragePath = $game->storage_path ? trim($game->storage_path) : '';
        $entryFile = $game->entry_file ? trim($game->entry_file) : 'index.html';

        // Check for fixture fallback in dev/test if storage_path is empty
        if (blank($rawStoragePath)) {
            $rawStoragePath = Config::get('games.fixtures_path', 'fixtures/games').'/neon-pulse';
        }

        // Security check: reject absolute URLs in storage_path
        if (Str::startsWith(strtolower($rawStoragePath), ['http://', 'https://', '//', 'ftp://'])) {
            throw new InvalidArgumentException("Absolute URLs are not permitted in game storage_path: [{$rawStoragePath}]");
        }

        // Security check: reject path traversal
        if (str_contains($rawStoragePath, '..') || str_contains($entryFile, '..') || str_contains($entryFile, '/') || str_contains($entryFile, '\\')) {
            throw new InvalidArgumentException('Path traversal detected in game storage_path or entry_file.');
        }

        // Normalize separators and trim slashes
        $normalizedPath = str_replace('\\', '/', $rawStoragePath);
        $normalizedPath = trim($normalizedPath, '/');

        // Sanitize and encode path segments safely
        $segments = array_filter(explode('/', $normalizedPath), fn ($seg) => $seg !== '' && $seg !== '.');
        $encodedSegments = array_map('rawurlencode', $segments);
        $sanitizedRelativePath = implode('/', $encodedSegments);

        $encodedEntryFile = rawurlencode($entryFile);
        $relativePath = "{$sanitizedRelativePath}/{$encodedEntryFile}";

        $embedOrigin = rtrim((string) Config::get('games.embed_origin', config('app.url')), '/');

        return "{$embedOrigin}/{$relativePath}";
    }

    /**
     * Get the configured iframe sandbox attributes for game isolation.
     */
    public function getSandboxAttributes(): string
    {
        return (string) Config::get(
            'games.iframe_sandbox',
            'allow-scripts allow-same-origin allow-forms allow-pointer-lock'
        );
    }
}
