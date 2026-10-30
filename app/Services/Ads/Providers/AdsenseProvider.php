<?php

namespace App\Services\Ads\Providers;

use App\Models\AdProvider;
use App\Services\Ads\Contracts\AdProviderInterface;

class AdsenseProvider implements AdProviderInterface
{
    protected ?AdProvider $model = null;

    public function __construct()
    {
        $this->model = AdProvider::where('key', 'adsense')->first();
    }

    public function getKey(): string
    {
        return 'adsense';
    }

    public function getName(): string
    {
        return $this->model?->name ?? (string) config('ads.providers.adsense.name', 'Google AdSense');
    }

    public function isEnabled(): bool
    {
        // Must be enabled in database/config AND have global ads enabled
        $globallyEnabled = (bool) config('ads.enabled', false);
        $providerEnabled = $this->model ? $this->model->is_enabled : (bool) config('ads.providers.adsense.enabled', false);

        return $globallyEnabled && $providerEnabled && $this->isConfigured();
    }

    public function isConfigured(): bool
    {
        $pubId = $this->getPublisherId();

        return ! empty($pubId) && str_starts_with($pubId, 'ca-pub-');
    }

    public function getPublisherId(): ?string
    {
        return $this->model?->publisher_id ?: config('ads.providers.adsense.publisher_id');
    }

    public function getScriptUrl(): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $pubId = $this->getPublisherId();

        return "https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={$pubId}";
    }

    public function isTestMode(): bool
    {
        return $this->model ? $this->model->test_mode : (bool) config('ads.providers.adsense.test_mode', true);
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
        ];
    }
}
