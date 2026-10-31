<?php

namespace App\Services;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Cache;

class PlatformSettingService
{
    public const CACHE_KEY = 'taph5_platform_settings_all';

    public const CACHE_TTL = 3600; // 1 hour

    /**
     * Default platform settings definitions.
     *
     * @var array<string, array{group: string, value: mixed, type: string, description: string}>
     */
    protected const DEFAULTS = [
        // General Group
        'site_name' => [
            'group' => 'general',
            'value' => 'TapH5 Games',
            'type' => 'string',
            'description' => 'The brand name displayed across the website and emails',
        ],
        'site_description' => [
            'group' => 'general',
            'value' => 'Play high-performance HTML5 games instantly online across mobile, tablet, and desktop without downloads.',
            'type' => 'string',
            'description' => 'General portal summary description',
        ],
        'contact_email' => [
            'group' => 'general',
            'value' => 'support@taph5gaming.com',
            'type' => 'string',
            'description' => 'Primary platform contact and inquiries email address',
        ],
        'default_language' => [
            'group' => 'general',
            'value' => 'en',
            'type' => 'string',
            'description' => 'Default interface language code',
        ],
        'default_timezone' => [
            'group' => 'general',
            'value' => 'UTC',
            'type' => 'string',
            'description' => 'Default platform timezone for scheduled reporting',
        ],
        'social_twitter' => [
            'group' => 'general',
            'value' => 'https://twitter.com',
            'type' => 'string',
            'description' => 'X (formerly Twitter) profile link',
        ],
        'social_discord' => [
            'group' => 'general',
            'value' => 'https://discord.gg',
            'type' => 'string',
            'description' => 'Community Discord server link',
        ],
        'social_youtube' => [
            'group' => 'general',
            'value' => 'https://youtube.com',
            'type' => 'string',
            'description' => 'Official YouTube channel link',
        ],

        // SEO Group
        'default_meta_title' => [
            'group' => 'seo',
            'value' => 'TapH5 Games — Play Free HTML5 Games Online',
            'type' => 'string',
            'description' => 'Default meta title for public pages when custom title is unset',
        ],
        'default_meta_description' => [
            'group' => 'seo',
            'value' => 'Play free HTML5 browser games on TapH5. Arcade, puzzle, action, and casual games with instant play and zero downloads.',
            'type' => 'string',
            'description' => 'Default meta description for public pages',
        ],
        'og_image' => [
            'group' => 'seo',
            'value' => '/images/og-default.png',
            'type' => 'string',
            'description' => 'Default Open Graph and Twitter sharing image path',
        ],
        'canonical_url' => [
            'group' => 'seo',
            'value' => '',
            'type' => 'string',
            'description' => 'Custom base canonical URL override (leave blank to auto-detect)',
        ],
        'robots_default' => [
            'group' => 'seo',
            'value' => 'index, follow',
            'type' => 'string',
            'description' => 'Default robots indexing directives',
        ],

        // Gameplay Group
        'default_games_per_page' => [
            'group' => 'gameplay',
            'value' => 18,
            'type' => 'integer',
            'description' => 'Number of games displayed per page in catalog view',
        ],
        'homepage_game_limit' => [
            'group' => 'gameplay',
            'value' => 6,
            'type' => 'integer',
            'description' => 'Default number of games displayed per homepage section',
        ],
        'related_games_limit' => [
            'group' => 'gameplay',
            'value' => 6,
            'type' => 'integer',
            'description' => 'Number of recommended games on game play screen',
        ],
        'enable_recommendations' => [
            'group' => 'gameplay',
            'value' => true,
            'type' => 'boolean',
            'description' => 'Enable dynamic algorithmic game recommendations below game frame',
        ],

        // Analytics Group
        'analytics_enabled' => [
            'group' => 'analytics',
            'value' => true,
            'type' => 'boolean',
            'description' => 'First-party privacy-compliant event ingestion',
        ],
        'data_retention_days' => [
            'group' => 'analytics',
            'value' => 90,
            'type' => 'integer',
            'description' => 'Retention window for granular raw analytics events (days)',
        ],
        'session_timeout_minutes' => [
            'group' => 'analytics',
            'value' => 30,
            'type' => 'integer',
            'description' => 'Inactivity period before ending active gameplay sessions',
        ],

        // Monetization Group
        'ads_enabled' => [
            'group' => 'monetization',
            'value' => false,
            'type' => 'boolean',
            'description' => 'Master switch for ad placements (simulation mode active)',
        ],
        'consent_mode_default' => [
            'group' => 'monetization',
            'value' => 'opt-in',
            'type' => 'string',
            'description' => 'Default cookie consent strategy for advertising tags',
        ],
    ];

    /**
     * Get a setting by key, falling back to predefined default if absent.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->getAll();

        if (array_key_exists($key, $all)) {
            return $all[$key];
        }

        if (array_key_exists($key, self::DEFAULTS)) {
            return self::DEFAULTS[$key]['value'];
        }

        return $default;
    }

    /**
     * Set a setting in the database and flush the cache.
     */
    public function set(string $key, mixed $value, string $group = 'general', string $type = 'string', ?string $description = null): void
    {
        $stringValue = match ($type) {
            'boolean' => $value ? '1' : '0',
            'integer' => (string) (int) $value,
            'json' => is_string($value) ? $value : json_encode($value),
            default => (string) ($value ?? ''),
        };

        PlatformSetting::updateOrCreate(
            ['key' => $key],
            [
                'group' => $group,
                'value' => $stringValue,
                'type' => $type,
                'description' => $description ?? (self::DEFAULTS[$key]['description'] ?? null),
            ]
        );

        $this->clearCache();
    }

    /**
     * Get all settings grouped by category.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getGrouped(): array
    {
        $this->seedDefaultsIfEmpty();

        $rows = PlatformSetting::all();
        $grouped = [];

        foreach ($rows as $row) {
            $grouped[$row->group][$row->key] = [
                'id' => $row->id,
                'key' => $row->key,
                'value' => $row->getTypedValue(),
                'type' => $row->type,
                'description' => $row->description,
            ];
        }

        return $grouped;
    }

    /**
     * Get key-value dictionary of all typed settings.
     *
     * @return array<string, mixed>
     */
    public function getAll(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            $this->seedDefaultsIfEmpty();

            $results = [];
            foreach (PlatformSetting::all() as $setting) {
                $results[$setting->key] = $setting->getTypedValue();
            }

            return $results;
        });
    }

    /**
     * Invalidate cached settings.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Seed initial default settings if table has no rows.
     */
    public function seedDefaultsIfEmpty(): void
    {
        if (PlatformSetting::count() === 0) {
            foreach (self::DEFAULTS as $key => $meta) {
                $strVal = match ($meta['type']) {
                    'boolean' => $meta['value'] ? '1' : '0',
                    'integer' => (string) $meta['value'],
                    'json' => json_encode($meta['value']),
                    default => (string) $meta['value'],
                };

                PlatformSetting::create([
                    'key' => $key,
                    'group' => $meta['group'],
                    'value' => $strVal,
                    'type' => $meta['type'],
                    'description' => $meta['description'],
                ]);
            }
        }
    }
}
