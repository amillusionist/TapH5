<?php

namespace App\Services\Ads\Providers;

use App\Models\AdProvider;
use App\Services\Ads\Contracts\AdProviderInterface;

/**
 * H5 Games Ads / Google Ad Placement API Provider Adapter
 *
 * ARCHITECTURAL NOTICE (Option B — Game-Document Integration):
 * Google Ad Placement API (adBreak / adConfig) operates inside the game execution context.
 * Because TapH5 executes HTML5 games inside a hardened sandboxed iframe without allow-same-origin,
 * arbitrary games cannot access parent DOM or parent AdSense tags.
 * Consequently, H5 Games Ads interstitial/rewarded breaks are integrated directly into each game's
 * document using the publisher's approved client ID, while the parent portal provides non-blocking
 * lifecycle coordination and origin-checked postMessage listeners.
 */
class H5GamesAdsProvider implements AdProviderInterface
{
    protected ?AdProvider $model = null;

    public function __construct()
    {
        $this->model = AdProvider::where('key', 'h5_games_ads')->first();
    }

    public function getKey(): string
    {
        return 'h5_games_ads';
    }

    public function getName(): string
    {
        return $this->model?->name ?? (string) config('ads.providers.h5_games_ads.name', 'H5 Games Ads');
    }

    public function isEnabled(): bool
    {
        $globallyEnabled = (bool) config('ads.enabled', false);
        $providerEnabled = $this->model ? $this->model->is_enabled : (bool) config('ads.providers.h5_games_ads.enabled', false);

        return $globallyEnabled && $providerEnabled && $this->isConfigured();
    }

    public function isConfigured(): bool
    {
        $clientId = $this->getPublisherId();

        return ! empty($clientId);
    }

    public function getPublisherId(): ?string
    {
        return $this->model?->publisher_id ?: config('ads.providers.h5_games_ads.publisher_id');
    }

    public function getScriptUrl(): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $clientId = $this->getPublisherId();

        return "https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={$clientId}";
    }

    public function isTestMode(): bool
    {
        return $this->model ? $this->model->test_mode : (bool) config('ads.providers.h5_games_ads.test_mode', true);
    }

    /**
     * @return array<string, mixed>
     */
    public function getPublicConfig(): array
    {
        return [
            'key' => $this->getKey(),
            'name' => $this->getName(),
            'enabled' => $this->isEnabled(),
            'configured' => $this->isConfigured(),
            'publisher_id' => $this->isConfigured() ? $this->getPublisherId() : null,
            'script_url' => $this->isEnabled() ? $this->getScriptUrl() : null,
            'test_mode' => $this->isTestMode(),
            'preload_ad_breaks' => $this->model?->settings['preload_ad_breaks'] ?? 'on',
            'integration_model' => 'game_document',
            'requires_sdk' => true,
        ];
    }
}
