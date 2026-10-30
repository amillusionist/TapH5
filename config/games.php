<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Game Storage & Origin Configuration
    |--------------------------------------------------------------------------
    |
    | In production, games can be served from a dedicated origin/subdomain
    | (e.g., https://games.taph5gaming.com) to provide maximum isolation
    | between untrusted game code and the core portal session/cookies.
    |
    */

    'storage_disk' => env('GAME_STORAGE_DISK', 'public'),

    'storage_path' => env('GAME_STORAGE_PATH', 'games'),

    'embed_origin' => env('GAME_EMBED_ORIGIN', env('APP_URL', 'http://localhost:8000')),

    'iframe_sandbox' => env(
        'GAME_IFRAME_SANDBOX',
        'allow-scripts allow-same-origin allow-forms allow-pointer-lock'
    ),

    'allowed_entry_files' => [
        'index.html',
        'game.html',
        'play.html',
    ],

    'fixtures_path' => 'fixtures/games',
];

