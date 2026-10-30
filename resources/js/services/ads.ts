/**
 * TapH5 Games — Monetization & Advertising Service
 *
 * Provides centralized provider management, consent enforcement,
 * script loading, and placement eligibility checks.
 *
 * Core principles:
 * - Disabled by default (ADS_ENABLED=false).
 * - Zero live ad scripts in local development without explicit configuration.
 * - Zero layout shifts (reserves layout dimensions when active).
 * - Full privacy consent verification before non-essential script injection.
 * - Non-blocking game loop fallbacks (gameplay never hangs if ads are absent/blocked).
 * - Prepared for Google-certified CMP (IAB TCF v2.2) integration without banner conflict.
 */

import type { PublicAdConfig, PublicAdPlacement, PublicAdProvider } from '@/types';

export type ConsentStatus = 'granted' | 'denied' | 'pending';

export interface StoredConsentRecord {
    schema_version: number;
    policy_version: string;
    status: 'granted' | 'denied';
    categories: {
        functional: boolean;
        advertising: boolean;
    };
    updated_at: string;
}

export const CONSENT_STORAGE_KEY = 'taph5_ad_consent';
export const CURRENT_CONSENT_SCHEMA_VERSION = 1;
export const DEFAULT_POLICY_VERSION = '2026-10-31';

const injectedScripts = new Set<string>();

/**
 * Retrieve the full structured consent record from local storage.
 * Handles legacy string formats ("granted"/"denied") by auto-upgrading them,
 * and validates policy version freshness.
 */
export function getStoredConsentRecord(policyVersion: string = DEFAULT_POLICY_VERSION): StoredConsentRecord | null {
    if (typeof window === 'undefined') return null;

    try {
        const raw = localStorage.getItem(CONSENT_STORAGE_KEY);
        if (!raw) return null;

        // Legacy format migration: raw string "granted" or "denied"
        if (raw === 'granted' || raw === 'denied') {
            const upgraded: StoredConsentRecord = {
                schema_version: CURRENT_CONSENT_SCHEMA_VERSION,
                policy_version: policyVersion,
                status: raw,
                categories: {
                    functional: true,
                    advertising: raw === 'granted',
                },
                updated_at: new Date().toISOString(),
            };
            localStorage.setItem(CONSENT_STORAGE_KEY, JSON.stringify(upgraded));
            return upgraded;
        }

        const parsed = JSON.parse(raw) as Partial<StoredConsentRecord>;
        if (
            parsed &&
            typeof parsed === 'object' &&
            parsed.schema_version === CURRENT_CONSENT_SCHEMA_VERSION &&
            (parsed.status === 'granted' || parsed.status === 'denied')
        ) {
            // Invalidate consent if legal policy version has been incremented
            if (policyVersion && parsed.policy_version && parsed.policy_version !== policyVersion) {
                return null;
            }
            return parsed as StoredConsentRecord;
        }

        return null;
    } catch {
        return null;
    }
}

/**
 * Get current visitor consent status for advertising technologies.
 */
export function getConsentStatus(policyVersion: string = DEFAULT_POLICY_VERSION): ConsentStatus {
    const record = getStoredConsentRecord(policyVersion);
    return record ? record.status : 'pending';
}

/**
 * Persist visitor advertising consent choice in structured, versioned JSON.
 */
export function setConsentStatus(
    status: 'granted' | 'denied',
    options?: {
        policyVersion?: string;
        categories?: { functional?: boolean; advertising?: boolean };
    }
): void {
    if (typeof window === 'undefined') return;

    try {
        const policyVersion = options?.policyVersion || DEFAULT_POLICY_VERSION;
        const record: StoredConsentRecord = {
            schema_version: CURRENT_CONSENT_SCHEMA_VERSION,
            policy_version: policyVersion,
            status,
            categories: {
                functional: true,
                advertising: options?.categories?.advertising ?? (status === 'granted'),
            },
            updated_at: new Date().toISOString(),
        };

        localStorage.setItem(CONSENT_STORAGE_KEY, JSON.stringify(record));
        window.dispatchEvent(new CustomEvent('taph5:consent-changed', { detail: { record, status } }));
    } catch {
        // Silently ignore storage failures (e.g. private mode quota limits)
    }
}

/**
 * Detect whether an IAB TCF v2.2 certified CMP is currently loaded on the page.
 */
export function isExternalCmpActive(): boolean {
    if (typeof window === 'undefined') return false;
    return typeof (window as any).__tcfapi === 'function';
}

/**
 * Determine whether the first-party consent modal should be shown to the visitor.
 * Suppressed if an external certified CMP is active or if configured for google_cmp mode.
 */
export function shouldShowFirstPartyConsent(config?: PublicAdConfig): boolean {
    if (!config?.consent_required) return false;
    if (config.consent_mode === 'google_cmp') return false;
    if (isExternalCmpActive()) return false;
    return getConsentStatus(config.consent_policy_version || DEFAULT_POLICY_VERSION) === 'pending';
}

/**
 * Determine whether advertising technologies are permitted under current consent.
 */
export function hasAdvertisingConsent(config?: PublicAdConfig): boolean {
    if (!config?.consent_required) {
        return true;
    }
    // If an external TCF CMP is present, ad tags communicate directly with __tcfapi
    if (isExternalCmpActive()) {
        return true;
    }
    return getConsentStatus(config.consent_policy_version || DEFAULT_POLICY_VERSION) === 'granted';
}

/**
 * Trigger the consent preferences modal dialog.
 */
export function openConsentPreferences(): void {
    if (typeof window === 'undefined') return;
    window.dispatchEvent(new CustomEvent('taph5:open-consent-modal'));
}

/**
 * Check whether client matches mobile screen threshold.
 */
export function isMobileDevice(): boolean {
    if (typeof window === 'undefined') return false;
    return window.innerWidth < 768;
}

/**
 * Determine whether a specific ad placement is currently eligible to render.
 */
export function isPlacementEligible(placementKey: string, config?: PublicAdConfig): boolean {
    if (!config || !config.ads_enabled) {
        return false;
    }

    if (config.consent_required && !hasAdvertisingConsent(config)) {
        return false;
    }

    const placement: PublicAdPlacement | undefined = config.placements[placementKey];
    if (!placement) {
        return false;
    }

    const isMobile = isMobileDevice();
    if (isMobile && !placement.show_on_mobile) {
        return false;
    }
    if (!isMobile && !placement.show_on_desktop) {
        return false;
    }

    const provider: PublicAdProvider | undefined = config.providers[placement.provider];
    if (!provider || !provider.enabled || !provider.configured) {
        return false;
    }

    return true;
}

/**
 * Dynamically inject and initialize an advertising provider SDK script.
 * Prevents duplicate tags, checks consent, and handles errors safely.
 */
export function loadProviderScript(providerKey: string, config: PublicAdConfig): Promise<boolean> {
    if (typeof window === 'undefined') return Promise.resolve(false);

    if (!config.ads_enabled || !hasAdvertisingConsent(config)) {
        return Promise.resolve(false);
    }

    const provider = config.providers[providerKey];
    if (!provider || !provider.enabled || !provider.script_url) {
        return Promise.resolve(false);
    }

    // Prevent duplicate injection
    if (injectedScripts.has(providerKey)) {
        return Promise.resolve(true);
    }

    // Check if script tag already exists in document
    const existing = document.querySelector(`script[data-ad-provider="${providerKey}"]`);
    if (existing) {
        injectedScripts.add(providerKey);
        return Promise.resolve(true);
    }

    return new Promise((resolve) => {
        const script = document.createElement('script');
        script.async = true;
        script.crossOrigin = 'anonymous';
        script.src = provider.script_url!;
        script.setAttribute('data-ad-provider', providerKey);

        if (provider.publisher_id) {
            script.setAttribute('data-ad-client', provider.publisher_id);
        }

        // H5 Games Ads test mode attribute
        if (providerKey === 'h5_games_ads' && provider.test_mode) {
            script.setAttribute('data-adbreak-test', 'on');
        }

        script.onload = () => {
            injectedScripts.add(providerKey);
            resolve(true);
        };

        script.onerror = () => {
            // Script load failed (e.g. ad blocker, offline, DNS failure)
            resolve(false);
        };

        document.head.appendChild(script);
    });
}

/**
 * Trigger official H5 Games Ads / Ad Placement API ad break opportunity.
 * Safely and non-blockingly falls through to gameplay if ads are disabled,
 * unconfigured, rejected, or encounter an error.
 */
export function triggerH5GameBreak(options: {
    type?: 'start' | 'pause' | 'next';
    name?: string;
    beforeAd?: () => void;
    afterAd?: () => void;
    config?: PublicAdConfig;
    timeoutMs?: number;
}): void {
    const {
        type = 'start',
        name = 'game_break',
        beforeAd,
        afterAd,
        config,
        timeoutMs = 5000,
    } = options;

    let hasResumed = false;
    const resumeGameplay = () => {
        if (!hasResumed) {
            hasResumed = true;
            afterAd?.();
        }
    };

    // Fast-path: If placement is not eligible or ads are disabled, resume instantly
    if (!isPlacementEligible('game_player_interstitial', config)) {
        beforeAd?.();
        resumeGameplay();
        return;
    }

    // Safety timeout: Never leave the user hanging if an ad SDK stalls
    const timer = setTimeout(() => {
        resumeGameplay();
    }, timeoutMs);

    const safeAfterAd = () => {
        clearTimeout(timer);
        resumeGameplay();
    };

    // Check if official Ad Placement API window.adBreak is present in current window
    const adBreak = (window as any).adBreak;

    if (typeof adBreak === 'function') {
        try {
            beforeAd?.();
            adBreak({
                type,
                name,
                beforeAd: () => {
                    // Ad is about to show
                },
                afterAd: () => {
                    safeAfterAd();
                },
                adBreakDone: () => {
                    safeAfterAd();
                },
            });
            return;
        } catch {
            // Fallthrough to game execution
            safeAfterAd();
            return;
        }
    }

    // Default fallback if SDK not ready or not in document
    beforeAd?.();
    safeAfterAd();
}
