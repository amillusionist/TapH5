<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Game Storage & Origin Configuration (Single-Domain Architecture)
    |--------------------------------------------------------------------------
    |
    | All portal pages and HTML5 games run on the same domain (e.g. taph5gaming.com
    | or http://127.0.0.1:8000 in local development).
    |
    | Games are stored in public/games/ and accessed via /games/{storage_path}/{entry_file}.
    |
    */

    // Private staging directory where newly imported game folders are placed for scanning
    'staging_root' => env('GAME_STAGING_ROOT', storage_path('staging')),

    // Public games storage root directory where published games reside
    'public_root' => env('GAME_PUBLIC_ROOT', public_path('games')),

    // Base URL origin for games (defaults to APP_URL on the single domain)
    'embed_origin' => env('GAME_EMBED_ORIGIN', env('APP_URL', 'http://127.0.0.1:8000')),

    // Relative web URL prefix where public games are served
    'url_prefix' => 'games',

    // Strict iframe sandboxing for untrusted game execution.
    // In single-domain architecture, omitting 'allow-same-origin' protects
    // the parent portal DOM, cookies, and localStorage from untrusted game JS.
    'iframe_sandbox' => env(
        'GAME_IFRAME_SANDBOX',
        'allow-scripts allow-forms allow-pointer-lock'
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
        'thumbnail.png', 'thumbnail.jpg', 'thumbnail.jpeg', 'thumbnail.webp', 'thumbnail.svg',
        'cover.png', 'cover.jpg', 'cover.jpeg', 'cover.webp', 'cover.svg',
        'screenshot.png', 'screenshot.jpg', 'screenshot.jpeg', 'screenshot.webp',
        'preview.png', 'preview.jpg', 'preview.jpeg', 'preview.webp',
        'poster.png', 'poster.jpg', 'poster.jpeg', 'poster.webp',
        'banner.png', 'banner.jpg', 'banner.jpeg', 'banner.webp',
        'icon.png', 'icon.jpg', 'icon.jpeg', 'icon.webp', 'icon.svg',
        'thumb.png', 'thumb.jpg', 'thumb.jpeg', 'thumb.webp',
        'logo.png', 'logo.jpg', 'logo.svg', 'logo.webp',
        'icon-512.png', 'icon-192.png',
    ],

    // Default portal fallback thumbnail if game does not provide one
    'default_thumbnail' => env('GAME_DEFAULT_THUMBNAIL', '/images/thumbnails/default-game.svg'),
];
