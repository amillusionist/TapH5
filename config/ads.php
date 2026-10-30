<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Global Monetization Master Switch
    |--------------------------------------------------------------------------
    |
    | Master toggle for platform advertisements. STRICTLY FALSE by default.
    | When false, zero ad components, slots, scripts, or placeholders are rendered.
    |
    */
    'enabled' => (bool) env('ADS_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Privacy & Consent Management
    |--------------------------------------------------------------------------
    |
    | When true, non-essential advertising scripts and cookies will not be loaded
    | until the visitor explicitly gives consent through the consent framework.
    |
    | Modes:
    | - 'first_party': TapH5 built-in consent modal and structured local storage.
    | - 'google_cmp': Certified Google CMP / IAB TCF v2.2 (e.g. AdSense Privacy & Messaging).
    |
    */
    'consent_required' => (bool) env('ADS_CONSENT_REQUIRED', true),
    'consent_mode' => env('ADS_CONSENT_MODE', 'first_party'),
    'consent_policy_version' => env('ADS_CONSENT_POLICY_VERSION', '2026-10-31'),

    /*
    |--------------------------------------------------------------------------
    | Default Advertising Provider
    |--------------------------------------------------------------------------
    |
    | Default provider key to use when a placement does not specify an override.
    |
    */
    'default_provider' => env('ADS_DEFAULT_PROVIDER', 'adsense'),

    /*
    |--------------------------------------------------------------------------
    | Supported Provider Configurations
    |--------------------------------------------------------------------------
    |
    | Configuration options for supported advertising providers.
    | Secrets and publisher IDs are loaded from environment variables.
    |
    */
    'providers' => [
        'adsense' => [
            'name' => 'Google AdSense',
            'enabled' => (bool) env('ADSENSE_ENABLED', false),
            'publisher_id' => env('ADSENSE_PUBLISHER_ID', null),
            'script_url' => 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js',
            'test_mode' => (bool) env('ADSENSE_TEST_MODE', true),
        ],
        'h5_games_ads' => [
            'name' => 'H5 Games Ads',
            'enabled' => (bool) env('H5_GAMES_ADS_ENABLED', false),
            'publisher_id' => env('H5_GAMES_ADS_CLIENT_ID', null),
            'test_mode' => (bool) env('H5_GAMES_ADS_TEST_MODE', true),
            'preload_ad_breaks' => 'on',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Placement Configuration Cache TTL
    |--------------------------------------------------------------------------
    |
    | Number of seconds to cache active ad placement configuration to prevent
    | repeated database queries on high-traffic portal pages.
    |
    */
    'cache_ttl' => (int) env('ADS_CACHE_TTL', 3600),
];
