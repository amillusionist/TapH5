<?php

namespace App\Services;

use App\Models\Game;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class GameUrlService
{
    /**
     * Resolve the public or isolated embed URL for a given game.
     */
    public function getEmbedUrl(Game $game): string
    {
        $storagePath = $game->storage_path ? trim($game->storage_path, '/') : '';
        $entryFile = $game->entry_file ?: 'index.html';

        if (blank($storagePath)) {
            // Default to technical fixture path if none provided
            $storagePath = Config::get('games.fixtures_path', 'fixtures/games').'/neon-pulse';
        }

        $relativePath = "{$storagePath}/{$entryFile}";

        // If the storage path is an absolute URL already, return it directly
        if (Str::startsWith($storagePath, ['http://', 'https://'])) {
            return "{$storagePath}/{$entryFile}";
        }

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
