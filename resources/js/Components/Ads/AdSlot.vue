<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { PageProps, PublicAdConfig, PublicAdPlacement, PublicAdProvider } from '@/types';
import { isPlacementEligible, loadProviderScript } from '@/services/ads';

const props = withDefaults(
    defineProps<{
        placementKey: string;
        className?: string;
        fallbackHeight?: string;
        showLabel?: boolean;
    }>(),
    {
        className: '',
        fallbackHeight: 'min-h-[90px]',
        showLabel: true,
    }
);

const page = usePage<PageProps>();
const isRendered = ref(false);
const hasConsent = ref(true);

const adConfig = computed<PublicAdConfig | undefined>(() => page.props.adConfig);

const placement = computed<PublicAdPlacement | undefined>(() => {
    return adConfig.value?.placements[props.placementKey];
});

const provider = computed<PublicAdProvider | undefined>(() => {
    if (!placement.value || !adConfig.value) return undefined;
    return adConfig.value.providers[placement.value.provider];
});

const isEligible = computed<boolean>(() => {
    return isPlacementEligible(props.placementKey, adConfig.value);
});

const initAdSlot = async () => {
    if (!isEligible.value || !provider.value || !adConfig.value) {
        return;
    }

    if (provider.value.key === 'adsense') {
        const loaded = await loadProviderScript('adsense', adConfig.value);
        if (loaded && !isRendered.value) {
            try {
                ((window as any).adsbygoogle = (window as any).adsbygoogle || []).push({});
                isRendered.value = true;
            } catch {
                // Ignore ad push errors
            }
        }
    }
};

const handleConsentChanged = () => {
    if (isEligible.value && !isRendered.value) {
        initAdSlot();
    }
};

onMounted(() => {
    if (typeof window !== 'undefined') {
        window.addEventListener('taph5:consent-changed', handleConsentChanged);
    }
    initAdSlot();
});

onUnmounted(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('taph5:consent-changed', handleConsentChanged);
    }
});
</script>

<template>
    <!-- Ad Container: completely unrendered when advertising is disabled to prevent CLS and empty boxes -->
    <div
        v-if="isEligible && placement && provider"
        :class="['w-full my-6 flex flex-col items-center justify-center transition-opacity duration-300', className]"
        role="region"
        aria-label="Advertisement"
    >
        <!-- Optional Micro Label -->
        <span
            v-if="showLabel"
            class="text-[10px] uppercase font-bold tracking-widest text-slate-400 mb-1.5 select-none"
        >
            Advertisement
        </span>

        <!-- Reserved Height Wrapper -->
        <div
            :class="['w-full flex items-center justify-center overflow-hidden bg-slate-900/30 rounded-xl border border-slate-800/40', fallbackHeight]"
        >
            <!-- Google AdSense Container -->
            <ins
                v-if="provider.key === 'adsense'"
                class="adsbygoogle w-full block text-center"
                style="display: block"
                :data-ad-client="provider.publisher_id || undefined"
                :data-ad-slot="placement.slot_id || undefined"
                :data-ad-format="placement.provider_config?.format || 'auto'"
                :data-full-width-responsive="placement.provider_config?.full_width_responsive ? 'true' : 'false'"
                :data-adtest="provider.test_mode ? 'on' : undefined"
            ></ins>

            <!-- Test Mode Indicator -->
            <div
                v-if="provider.test_mode"
                class="py-4 px-6 text-center select-none"
            >
                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-mono font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                    <span>Ad Placement: {{ placement.key }} ({{ provider.name }} Test Mode)</span>
                </div>
            </div>
        </div>
    </div>
</template>

