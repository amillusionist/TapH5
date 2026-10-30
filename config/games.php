<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Game Storage & Origin Configuration
    |--------------------------------------------------------------------------
    |
    | Phase 2 separates private staging storage, public game storage, and the
    | public embed origin. In production, games are served from an isolated
    | subdomain (e.g. games.taph5gaming.com).
    |
    */

    // Private staging directory where new game folders are copied for scanning
    'staging_root' => env('GAME_STAGING_ROOT', storage_path('staging')),

    // Public game storage directory where approved games are published
    'public_root' => env('GAME_PUBLIC_ROOT', public_path('games')),

    // Public embed origin for games (e.g. https://games.taph5gaming.com)
    'embed_origin' => env('GAME_EMBED_ORIGIN', env('APP_URL', 'http://localhost:8000')),

    // Staging and public disk definitions if using Laravel storage
    'storage_disk' => env('GAME_STORAGE_DISK', 'public'),
    'storage_path' => env('GAME_STORAGE_PATH', 'games'),

    // Strict iframe sandboxing for origin isolation
    'iframe_sandbox' => env(
        'GAME_IFRAME_SANDBOX',
        'allow-scripts allow-same-origin allow-forms allow-pointer-lock'
    ),

    // Candidate entry files in order of preference
    'allowed_entry_files' => [
        'index.html',
        'game.html',
        'play.html',
    ],

    // Default technical test fixture path
    'fixtures_path' => 'fixtures/games',

    /*
    |--------------------------------------------------------------------------
    | Scanner Limits & Security
    |--------------------------------------------------------------------------
    */
    'scan_enabled' => (bool) env('GAME_SCAN_ENABLED', true),

    // Max number of files allowed in a single game folder
    'max_files' => (int) env('GAME_SCAN_MAX_FILES', 5000),

    // Max individual file size in bytes (default 50MB)
    'max_file_size' => (int) env('GAME_SCAN_MAX_FILE_SIZE', 52428800),

    // Max total directory size in bytes (default 500MB)
    'max_total_size' => (int) env('GAME_SCAN_MAX_TOTAL_SIZE', 524288000),

    // Allowed file extensions for static HTML5 game assets
    'allowed_extensions' => [
        'html', 'htm',
        'css',
        'js', 'mjs',
        'json',
        'png', 'jpg', 'jpeg', 'webp', 'svg', 'gif', 'ico',
        'mp3', 'wav', 'ogg', 'm4a', 'aac',
        'mp4', 'webm', 'ogv',
        'woff', 'woff2', 'ttf', 'otf', 'eot',
        'xml', 'txt', 'csv',
        'wasm', 'data', 'bin', 'atlas',
    ],

    // Dangerous/forbidden extensions that trigger automatic rejection
    'forbidden_extensions' => [
        'php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'php8', 'phps',
        'cgi', 'pl', 'py', 'sh', 'bash', 'exe', 'bat', 'cmd', 'ps1',
        'vbs', 'dll', 'so', 'dylib', 'com', 'scr', 'msi', 'jar',
        'htaccess', 'env',
    ],

    // Thumbnail candidate filenames in search priority
    'thumbnail_candidates' => [
        'thumbnail.png',
        'thumbnail.jpg',
        'thumbnail.jpeg',
        'thumbnail.webp',
        'cover.png',
        'cover.jpg',
        'cover.jpeg',
        'cover.webp',
        'thumb.png',
        'thumb.jpg',
        'icon.png',
        'icon.jpg',
        'icon-512.png',
        'icon-192.png',
    ],
];
