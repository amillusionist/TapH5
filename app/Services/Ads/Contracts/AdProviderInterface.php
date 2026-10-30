<?php

namespace App\Services\Ads\Contracts;

interface AdProviderInterface
{
    /**
     * Get unique provider key (e.g. 'adsense', 'h5_games_ads').
     */
    public function getKey(): string;

    /**
     * Get human-readable provider name.
     */
    public function getName(): string;

    /**
     * Determine whether the provider is actively enabled.
     */
    public function isEnabled(): bool;

    /**
     * Determine whether the provider has required credentials configured.
     */
    public function isConfigured(): bool;

    /**
     * Get publisher or client ID.
     */
    public function getPublisherId(): ?string;

    /**
     * Get official SDK or loader script URL.
     */
    public function getScriptUrl(): ?string;

    /**
     * Determine whether the provider runs in test/sandbox mode.
     */
    public function isTestMode(): bool;

    /**
     * Get safe configuration array for client-side rendering.
     *
     * @return array<string, mixed>
     */
    public function getPublicConfig(): array;
}
