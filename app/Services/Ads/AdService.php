<?php

namespace App\Services\Ads;

use App\Models\AdPlacement;
use App\Models\AdProvider;
use App\Services\Ads\Contracts\AdProviderInterface;
use App\Services\Ads\Providers\AdsenseProvider;
use App\Services\Ads\Providers\H5GamesAdsProvider;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

class AdService
{
    public const CACHE_KEY_PLACEMENTS = 'taph5_active_ad_placements';

    public const CACHE_KEY_PUBLIC_CONFIG = 'taph5_public_ad_config';

    /**
     * @var array<string, AdProviderInterface>|null
     */
    protected ?array $providers = null;

    /**
     * Determine whether monetization is globally enabled.
     */
    public function isGloballyEnabled(): bool
    {
        return (bool) config('ads.enabled', false);
    }

    /**
     * Determine whether privacy consent is required before ad script execution.
     */
    public function isConsentRequired(): bool
    {
        return (bool) config('ads.consent_required', true);
    }

    /**
     * Get current consent enforcement mode ('first_party' or 'google_cmp').
     */
    public function getConsentMode(): string
    {
        return (string) config('ads.consent_mode', 'first_party');
    }

    /**
     * Get current consent policy version for expiration and re-consent checks.
     */
    public function getConsentPolicyVersion(): string
    {
        return (string) config('ads.consent_policy_version', '2026-10-31');
    }

    /**
     * Retrieve an ad provider instance by its identifier.
     */
    public function getProvider(string $key): ?AdProviderInterface
    {
        $providers = $this->getAllProviders();

        return $providers[$key] ?? null;
    }

    /**
     * Retrieve all registered advertising provider adapters.
     *
     * @return array<string, AdProviderInterface>
     */
    public function getAllProviders(): array
    {
        if ($this->providers === null) {
            $this->providers = [
                'adsense' => new AdsenseProvider,
                'h5_games_ads' => new H5GamesAdsProvider,
            ];
        }

        return $this->providers;
    }

    /**
     * Retrieve all active placements respecting global and provider status.
     *
     * @return Collection<int, AdPlacement>
     */
    public function getActivePlacements(): Collection
    {
        if (! $this->isGloballyEnabled()) {
            return new Collection;
        }

        return Cache::remember(
            self::CACHE_KEY_PLACEMENTS,
            config('ads.cache_ttl', 3600),
            function () {
                $placements = AdPlacement::enabled()->ordered()->get();

                // Filter out placements whose underlying provider is disabled or unconfigured
                return $placements->filter(function (AdPlacement $placement) {
                    $provider = $this->getProvider($placement->provider);

                    return $provider && $provider->isEnabled();
                })->values();
            }
        );
    }

    /**
     * Retrieve a specific placement by key.
     */
    public function getPlacement(string $key): ?AdPlacement
    {
        return AdPlacement::where('key', $key)->first();
    }

    /**
     * Compile public advertising configuration payload for frontend consumers.
     *
     * @return array<string, mixed>
     */
    public function getPublicAdConfig(): array
    {
        if (! $this->isGloballyEnabled()) {
            return [
                'ads_enabled' => false,
                'consent_required' => $this->isConsentRequired(),
                'consent_mode' => $this->getConsentMode(),
                'consent_policy_version' => $this->getConsentPolicyVersion(),
                'default_provider' => config('ads.default_provider', 'adsense'),
                'providers' => [],
                'placements' => (object) [],
            ];
        }

        return Cache::remember(
            self::CACHE_KEY_PUBLIC_CONFIG,
            config('ads.cache_ttl', 3600),
            function () {
                $providers = [];
                foreach ($this->getAllProviders() as $key => $provider) {
                    $providers[$key] = $provider->getPublicConfig();
                }

                $placements = [];
                foreach ($this->getActivePlacements() as $placement) {
                    $placements[$placement->key] = [
                        'key' => $placement->key,
                        'name' => $placement->name,
                        'provider' => $placement->provider,
                        'slot_id' => $placement->slot_id,
                        'placement_type' => $placement->placement_type,
                        'show_on_desktop' => $placement->show_on_desktop,
                        'show_on_mobile' => $placement->show_on_mobile,
                        'frequency_limit' => $placement->frequency_limit,
                        'provider_config' => $placement->provider_config ?? [],
                    ];
                }

                return [
                    'ads_enabled' => true,
                    'consent_required' => $this->isConsentRequired(),
                    'consent_mode' => $this->getConsentMode(),
                    'consent_policy_version' => $this->getConsentPolicyVersion(),
                    'default_provider' => config('ads.default_provider', 'adsense'),
                    'providers' => $providers,
                    'placements' => (object) $placements,
                ];
            }
        );
    }

    /**
     * Compile comprehensive monetization overview for Admin Control Panel.
     *
     * @return array<string, mixed>
     */
    public function getAdminOverview(): array
    {
        $providers = AdProvider::all()->map(function (AdProvider $model) {
            $isConfigured = $model->isConfigured();
            $isEnabled = $model->is_enabled;
            $globallyEnabled = $this->isGloballyEnabled();

            // Status resolution:
            // - For H5 Games Ads: specifically clarify in-game SDK and account approval requirement (Option B)
            // - Enabled & Live (only if globally enabled, provider enabled, configured)
            // - Ready for Activation (configured and provider enabled, but waiting for global toggle)
            // - Awaiting Credentials / Not Configured (missing publisher ID)
            // - Disabled (explicitly turned off)
            $statusText = match (true) {
                $model->key === 'h5_games_ads' && ! $isConfigured => 'Prepared (Requires Account Approval & Game SDK)',
                $model->key === 'h5_games_ads' && $isConfigured && ! $isEnabled => 'Prepared (SDK Ready, Disabled)',
                $model->key === 'h5_games_ads' && $isConfigured && $globallyEnabled && $isEnabled => 'Live & Active (Game SDK)',
                $model->key === 'h5_games_ads' && $isConfigured => 'Ready for Activation (Requires Game SDK)',
                $globallyEnabled && $isEnabled && $isConfigured => 'Live & Active',
                $isEnabled && $isConfigured => 'Ready for Activation',
                ! $isConfigured => 'Awaiting Account Configuration',
                default => 'Disabled',
            };

            return [
                'id' => $model->id,
                'key' => $model->key,
                'name' => $model->name,
                'is_enabled' => $model->is_enabled,
                'is_configured' => $isConfigured,
                'status_text' => $statusText,
                'publisher_id' => $model->publisher_id,
                'test_mode' => $model->test_mode,
                'settings' => $model->settings ?? [],
                'updated_at' => $model->updated_at?->diffForHumans(),
            ];
        });

        $placements = AdPlacement::ordered()->get()->map(function (AdPlacement $p) {
            return [
                'id' => $p->id,
                'key' => $p->key,
                'name' => $p->name,
                'description' => $p->description,
                'provider' => $p->provider,
                'slot_id' => $p->slot_id,
                'placement_type' => $p->placement_type,
                'is_enabled' => $p->is_enabled,
                'show_on_desktop' => $p->show_on_desktop,
                'show_on_mobile' => $p->show_on_mobile,
                'frequency_limit' => $p->frequency_limit,
                'priority' => $p->priority,
                'provider_config' => $p->provider_config ?? [],
            ];
        });

        $totalPlacements = $placements->count();
        $activePlacements = $placements->where('is_enabled', true)->count();
        $configuredProviders = $providers->where('is_configured', true)->count();

        return [
            'global' => [
                'is_enabled' => $this->isGloballyEnabled(),
                'consent_required' => $this->isConsentRequired(),
                'consent_mode' => $this->getConsentMode(),
                'consent_policy_version' => $this->getConsentPolicyVersion(),
                'default_provider' => config('ads.default_provider', 'adsense'),
            ],
            'stats' => [
                'total_placements' => $totalPlacements,
                'active_placements' => $activePlacements,
                'disabled_placements' => $totalPlacements - $activePlacements,
                'configured_providers' => $configuredProviders,
                'total_providers' => $providers->count(),
            ],
            'providers' => $providers,
            'placements' => $placements,
        ];
    }

    /**
     * Update an ad provider record.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateProvider(string $key, array $data): AdProvider
    {
        $provider = AdProvider::where('key', $key)->firstOrFail();

        $provider->update([
            'is_enabled' => (bool) ($data['is_enabled'] ?? $provider->is_enabled),
            'publisher_id' => array_key_exists('publisher_id', $data) ? $data['publisher_id'] : $provider->publisher_id,
            'test_mode' => (bool) ($data['test_mode'] ?? $provider->test_mode),
            'settings' => array_key_exists('settings', $data) ? $data['settings'] : $provider->settings,
        ]);

        $this->clearCache();

        return $provider;
    }

    /**
     * Update an ad placement record.
     *
     * @param  array<string, mixed>  $data
     */
    public function updatePlacement(int $id, array $data): AdPlacement
    {
        $placement = AdPlacement::findOrFail($id);

        if (isset($data['provider']) && ! in_array($data['provider'], array_keys($this->getAllProviders()), true)) {
            throw new InvalidArgumentException("Unsupported ad provider: {$data['provider']}");
        }

        $placement->update([
            'name' => $data['name'] ?? $placement->name,
            'description' => array_key_exists('description', $data) ? $data['description'] : $placement->description,
            'provider' => $data['provider'] ?? $placement->provider,
            'slot_id' => array_key_exists('slot_id', $data) ? $data['slot_id'] : $placement->slot_id,
            'placement_type' => $data['placement_type'] ?? $placement->placement_type,
            'is_enabled' => (bool) ($data['is_enabled'] ?? $placement->is_enabled),
            'show_on_desktop' => (bool) ($data['show_on_desktop'] ?? $placement->show_on_desktop),
            'show_on_mobile' => (bool) ($data['show_on_mobile'] ?? $placement->show_on_mobile),
            'frequency_limit' => (int) ($data['frequency_limit'] ?? $placement->frequency_limit),
            'priority' => (int) ($data['priority'] ?? $placement->priority),
            'provider_config' => array_key_exists('provider_config', $data) ? $data['provider_config'] : $placement->provider_config,
        ]);

        $this->clearCache();

        return $placement;
    }

    /**
     * Invalidate all cached ad configurations.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY_PLACEMENTS);
        Cache::forget(self::CACHE_KEY_PUBLIC_CONFIG);
        $this->providers = null;
    }
}
