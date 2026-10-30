<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import type { PageProps } from '@/types';
import { getConsentStatus, setConsentStatus, shouldShowFirstPartyConsent } from '@/services/ads';

const page = usePage<PageProps>();

const isOpen = ref(false);
const isCustomizing = ref(false);

const functionalConsent = ref(true); // Always true / required
const advertisingConsent = ref(false);

const currentPolicyVersion = computed(() => {
    return page.props.adConfig?.consent_policy_version || '2026-10-31';
});

const checkConsent = () => {
    if (shouldShowFirstPartyConsent(page.props.adConfig)) {
        isOpen.value = true;
    } else {
        const status = getConsentStatus(currentPolicyVersion.value);
        advertisingConsent.value = status === 'granted';
    }
};

const openModal = () => {
    advertisingConsent.value = getConsentStatus(currentPolicyVersion.value) === 'granted';
    isCustomizing.value = false;
    isOpen.value = true;
};

const acceptAll = () => {
    advertisingConsent.value = true;
    setConsentStatus('granted', {
        policyVersion: currentPolicyVersion.value,
        categories: { functional: true, advertising: true },
    });
    isOpen.value = false;
};

const rejectNonEssential = () => {
    advertisingConsent.value = false;
    setConsentStatus('denied', {
        policyVersion: currentPolicyVersion.value,
        categories: { functional: true, advertising: false },
    });
    isOpen.value = false;
};

const savePreferences = () => {
    const status = advertisingConsent.value ? 'granted' : 'denied';
    setConsentStatus(status, {
        policyVersion: currentPolicyVersion.value,
        categories: { functional: true, advertising: advertisingConsent.value },
    });
    isOpen.value = false;
};

onMounted(() => {
    if (typeof window !== 'undefined') {
        window.addEventListener('taph5:open-consent-modal', openModal);
        checkConsent();
    }
});

onUnmounted(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('taph5:open-consent-modal', openModal);
    }
});
</script>

<template>
    <div
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4 bg-black/70 backdrop-blur-sm animate-fade-in"
        role="dialog"
        aria-labelledby="consent-title"
        aria-modal="true"
    >
        <div class="w-full max-w-xl rounded-2xl bg-slate-900 border border-slate-800 shadow-2xl p-6 sm:p-7 space-y-5 text-slate-200">
            <!-- Header -->
            <div class="flex items-start gap-4">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h2 id="consent-title" class="text-lg font-bold text-white tracking-tight">
                        Privacy & Advertising Preferences
                    </h2>
                    <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                        We value your privacy. TapH5 Games uses essential cookies to deliver core features and optionally personalized or non-personalized advertisements to support free gaming.
                    </p>
                </div>
            </div>

            <!-- Granular Choices (when user clicks "Customize") -->
            <div v-if="isCustomizing" class="space-y-3 p-4 rounded-xl bg-slate-950/60 border border-slate-800 text-xs">
                <!-- Functional Cookies -->
                <div class="flex items-center justify-between gap-3 pb-3 border-b border-slate-800/80">
                    <div>
                        <div class="font-bold text-white flex items-center gap-1.5">
                            <span>Essential & Functional</span>
                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-emerald-500/10 text-emerald-400 font-mono">Always Active</span>
                        </div>
                        <p class="text-slate-400 text-[11px] mt-0.5">Required for game session state, security tokens, and navigation.</p>
                    </div>
                    <input
                        type="checkbox"
                        checked
                        disabled
                        aria-label="Essential and functional cookies"
                        class="rounded bg-slate-800 border-slate-700 text-emerald-500 opacity-60 cursor-not-allowed"
                    />
                </div>

                <!-- Advertising Cookies -->
                <div class="flex items-center justify-between gap-3 pt-1">
                    <div>
                        <div class="font-bold text-white">Advertising & Telemetry</div>
                        <p class="text-slate-400 text-[11px] mt-0.5">Allows display ads and partner ad networks (Google AdSense / H5 Games Ads) to support our free portal.</p>
                    </div>
                    <input
                        v-model="advertisingConsent"
                        type="checkbox"
                        aria-label="Advertising and telemetry cookies"
                        class="w-4 h-4 rounded bg-slate-800 border-slate-700 text-emerald-500 focus:ring-emerald-400 focus:ring-offset-slate-900 cursor-pointer"
                    />
                </div>
            </div>

            <!-- Policy Links -->
            <div class="text-[11px] text-slate-400">
                Learn more in our
                <Link :href="route('pages.cookie-policy')" class="text-emerald-400 underline hover:text-emerald-300">Cookie Policy</Link>
                and
                <Link :href="route('pages.privacy')" class="text-emerald-400 underline hover:text-emerald-300">Privacy Policy</Link>.
                You can change preferences anytime via the footer.
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row items-center justify-end gap-2.5 pt-2">
                <button
                    v-if="!isCustomizing"
                    @click="isCustomizing = true"
                    type="button"
                    class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition-colors text-center"
                >
                    Customize
                </button>

                <button
                    v-if="isCustomizing"
                    @click="savePreferences"
                    type="button"
                    class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold transition-colors"
                >
                    Save Preferences
                </button>

                <button
                    @click="rejectNonEssential"
                    type="button"
                    class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-slate-800/80 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-700 text-xs font-semibold transition-colors"
                >
                    Reject Non-Essential
                </button>

                <button
                    @click="acceptAll"
                    type="button"
                    class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-xs font-bold transition-all shadow-md shadow-emerald-500/20"
                >
                    Accept All
                </button>
            </div>
        </div>
    </div>
</template>

