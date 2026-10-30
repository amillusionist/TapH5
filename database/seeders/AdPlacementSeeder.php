<?php

namespace Database\Seeders;

use App\Models\AdPlacement;
use App\Models\AdProvider;
use Illuminate\Database\Seeder;

class AdPlacementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Initial Supported Providers (Disabled by default)
        $providers = [
            [
                'key' => 'adsense',
                'name' => 'Google AdSense',
                'is_enabled' => false,
                'publisher_id' => null,
                'test_mode' => true,
                'settings' => [
                    'auto_ads' => false,
                    'responsive' => true,
                ],
            ],
            [
                'key' => 'h5_games_ads',
                'name' => 'H5 Games Ads',
                'is_enabled' => false,
                'publisher_id' => null,
                'test_mode' => true,
                'settings' => [
                    'preload_ad_breaks' => 'on',
                    'sound' => 'on',
                ],
            ],
        ];

        foreach ($providers as $provider) {
            AdProvider::firstOrCreate(['key' => $provider['key']], $provider);
        }

        // 2. Initial Placement Inventory (Disabled by default)
        $placements = [
            [
                'key' => 'home_top',
                'name' => 'Homepage Top Leaderboard',
                'description' => 'Top banner placement displayed above homepage content and hero section.',
                'provider' => 'adsense',
                'slot_id' => null,
                'placement_type' => 'banner',
                'is_enabled' => false,
                'show_on_desktop' => true,
                'show_on_mobile' => true,
                'frequency_limit' => 0,
                'priority' => 10,
                'provider_config' => ['format' => 'auto', 'full_width_responsive' => true],
            ],
            [
                'key' => 'home_between_sections',
                'name' => 'Homepage Between Sections',
                'description' => 'Horizontal display ad positioned between homepage genre sections.',
                'provider' => 'adsense',
                'slot_id' => null,
                'placement_type' => 'display',
                'is_enabled' => false,
                'show_on_desktop' => true,
                'show_on_mobile' => true,
                'frequency_limit' => 0,
                'priority' => 20,
                'provider_config' => ['format' => 'auto', 'full_width_responsive' => true],
            ],
            [
                'key' => 'games_listing',
                'name' => 'All Games Listing Header',
                'description' => 'Prominent display ad above the games catalog filter and search area.',
                'provider' => 'adsense',
                'slot_id' => null,
                'placement_type' => 'display',
                'is_enabled' => false,
                'show_on_desktop' => true,
                'show_on_mobile' => true,
                'frequency_limit' => 0,
                'priority' => 30,
                'provider_config' => ['format' => 'auto', 'full_width_responsive' => true],
            ],
            [
                'key' => 'games_between_cards',
                'name' => 'Games Grid In-Feed Ad',
                'description' => 'In-feed ad slot naturally integrated inside the responsive game card grid.',
                'provider' => 'adsense',
                'slot_id' => null,
                'placement_type' => 'display',
                'is_enabled' => false,
                'show_on_desktop' => true,
                'show_on_mobile' => false,
                'frequency_limit' => 0,
                'priority' => 40,
                'provider_config' => ['layout_key' => '-fb+5w+4e-db+86'],
            ],
            [
                'key' => 'game_detail_top',
                'name' => 'Game Page Top Banner',
                'description' => 'Horizontal header banner positioned above game stage area.',
                'provider' => 'adsense',
                'slot_id' => null,
                'placement_type' => 'banner',
                'is_enabled' => false,
                'show_on_desktop' => true,
                'show_on_mobile' => true,
                'frequency_limit' => 0,
                'priority' => 50,
                'provider_config' => ['format' => 'auto'],
            ],
            [
                'key' => 'game_player_below',
                'name' => 'Below Game Player Banner',
                'description' => 'High-engagement display unit positioned directly beneath the game canvas toolbar.',
                'provider' => 'adsense',
                'slot_id' => null,
                'placement_type' => 'display',
                'is_enabled' => false,
                'show_on_desktop' => true,
                'show_on_mobile' => true,
                'frequency_limit' => 0,
                'priority' => 60,
                'provider_config' => ['format' => 'auto', 'full_width_responsive' => true],
            ],
            [
                'key' => 'game_detail_content',
                'name' => 'Game Detail Description Footer',
                'description' => 'Ad slot placed beneath gameplay instructions and metadata section.',
                'provider' => 'adsense',
                'slot_id' => null,
                'placement_type' => 'display',
                'is_enabled' => false,
                'show_on_desktop' => true,
                'show_on_mobile' => true,
                'frequency_limit' => 0,
                'priority' => 70,
                'provider_config' => ['format' => 'auto'],
            ],
            [
                'key' => 'game_player_interstitial',
                'name' => 'Game Level / Interstitial Break',
                'description' => 'Gameplay-related interstitial opportunity (e.g., game start or level pause) powered by H5 Games Ads.',
                'provider' => 'h5_games_ads',
                'slot_id' => null,
                'placement_type' => 'interstitial',
                'is_enabled' => false,
                'show_on_desktop' => true,
                'show_on_mobile' => true,
                'frequency_limit' => 120, // max once every 2 minutes
                'priority' => 80,
                'provider_config' => ['break_type' => 'start'],
            ],
        ];

        foreach ($placements as $placement) {
            AdPlacement::firstOrCreate(['key' => $placement['key']], $placement);
        }
    }
}
