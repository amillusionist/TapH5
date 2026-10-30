<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminPageHeader from '@/Components/Admin/AdminPageHeader.vue';
import ConfirmModal from '@/Components/Admin/ConfirmModal.vue';

interface ProviderItem {
    id: number;
    key: string;
    name: string;
    is_enabled: boolean;
    is_configured: boolean;
    status_text: string;
    publisher_id: string | null;
    test_mode: boolean;
    settings: Record<string, any>;
    updated_at: string | null;
}

interface PlacementItem {
    id: number;
    key: string;
    name: string;
    description: string | null;
    provider: string;
    slot_id: string | null;
    placement_type: string;
    is_enabled: boolean;
    show_on_desktop: boolean;
    show_on_mobile: boolean;
    frequency_limit: number;
    priority: number;
    provider_config: Record<string, any>;
}

interface MonetizationOverview {
    global: {
        is_enabled: boolean;
        consent_required: boolean;
        consent_mode: string;
        consent_policy_version: string;
        default_provider: string;
    };
    stats: {
        total_placements: number;
        active_placements: number;
        disabled_placements: number;
        configured_providers: number;
        total_providers: number;
    };
    providers: ProviderItem[];
    placements: PlacementItem[];
}

const props = defineProps<{
    monetization: MonetizationOverview;
}>();

// Editing placement state
const activePlacementModal = ref<PlacementItem | null>(null);

const placementForm = useForm({
    name: '',
    description: '',
    provider: 'adsense',
    slot_id: '',
    placement_type: 'display',
    is_enabled: false,
    show_on_desktop: true,
    show_on_mobile: true,
    frequency_limit: 0,
    priority: 0,
});

const openEditPlacement = (placement: PlacementItem) => {
    activePlacementModal.value = placement;
    placementForm.name = placement.name;
    placementForm.description = placement.description || '';
    placementForm.provider = placement.provider;
    placementForm.slot_id = placement.slot_id || '';
    placementForm.placement_type = placement.placement_type;
    placementForm.is_enabled = placement.is_enabled;
    placementForm.show_on_desktop = placement.show_on_desktop;
    placementForm.show_on_mobile = placement.show_on_mobile;
    placementForm.frequency_limit = placement.frequency_limit;
    placementForm.priority = placement.priority;
};

const closePlacementModal = () => {
    activePlacementModal.value = null;
    placementForm.reset();
};

const submitPlacementUpdate = () => {
    if (!activePlacementModal.value) return;

    placementForm.put(route('admin.monetization.placements.update', { id: activePlacementModal.value.id }), {
        preserveScroll: true,
        onSuccess: () => {
            closePlacementModal();
        },
    });
};

// Provider forms
const adsenseForm = useForm({
    is_enabled: props.monetization.providers.find(p => p.key === 'adsense')?.is_enabled ?? false,
    publisher_id: props.monetization.providers.find(p => p.key === 'adsense')?.publisher_id ?? '',
    test_mode: props.monetization.providers.find(p => p.key === 'adsense')?.test_mode ?? true,
});

const h5Form = useForm({
    is_enabled: props.monetization.providers.find(p => p.key === 'h5_games_ads')?.is_enabled ?? false,
    publisher_id: props.monetization.providers.find(p => p.key === 'h5_games_ads')?.publisher_id ?? '',
    test_mode: props.monetization.providers.find(p => p.key === 'h5_games_ads')?.test_mode ?? true,
});

const submitAdsenseUpdate = () => {
    adsenseForm.put(route('admin.monetization.providers.update', { key: 'adsense' }), {
        preserveScroll: true,
    });
};

const submitH5Update = () => {
    h5Form.put(route('admin.monetization.providers.update', { key: 'h5_games_ads' }), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AdminLayout title="Monetization & Advertising">
        <Head title="Monetization & Advertising — TapH5 Admin" />

        <div class="space-y-8 max-w-7xl mx-auto">
            <!-- Page Header -->
            <AdminPageHeader
                title="Monetization & Advertising"
                description="Manage advertising providers, configure ad slot IDs, and control device-specific placement inventory."
                :breadcrumbs="[{ label: 'Admin', href: route('admin.dashboard') }, { label: 'Monetization' }]"
            />

            <!-- Global Status Alert Banner -->
            <div
                class="p-5 sm:p-6 rounded-2xl border flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4"
                :class="monetization.global.is_enabled
                    ? 'bg-emerald-950/20 border-emerald-500/30'
                    : 'bg-amber-950/20 border-amber-500/30'"
            >
                <div class="flex items-start gap-4">
                    <div
                        class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                        :class="monetization.global.is_enabled
                            ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20'
                            : 'bg-amber-500/10 text-amber-400 border border-amber-500/20'"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm sm:text-base font-bold text-white">
                                Global Monetization Master State:
                                <span :class="monetization.global.is_enabled ? 'text-emerald-400' : 'text-amber-400'">
                                    {{ monetization.global.is_enabled ? 'ENABLED' : 'DISABLED (SAFE LOCAL MODE)' }}
                                </span>
                            </h3>
                            <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold uppercase tracking-wider bg-slate-800 text-slate-300">
                                ADS_ENABLED={{ monetization.global.is_enabled ? 'true' : 'false' }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 mt-1 leading-relaxed max-w-2xl">
                            All advertising rendering is completely disabled by default. No live scripts, third-party network requests, or empty ad containers will render until environment approval is configured.
                        </p>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-2 text-xs font-mono text-slate-400">
                    <div class="bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-800">
                        Consent: <span class="text-emerald-400 font-bold">{{ monetization.global.consent_required ? 'Required' : 'Disabled' }}</span>
                        ({{ monetization.global.consent_mode === 'google_cmp' ? 'Google CMP / TCF v2.2' : 'First-Party' }})
                    </div>
                    <div class="bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-800">
                        Policy: <span class="text-cyan-400 font-bold">v{{ monetization.global.consent_policy_version }}</span>
                    </div>
                </div>
            </div>

            <!-- Stats Overview Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- 1. Total Placements -->
                <div class="p-5 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 shadow-md">
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Total Placements</div>
                    <div class="text-2xl font-black text-white">{{ monetization.stats.total_placements }}</div>
                    <div class="text-[11px] text-slate-400 mt-1">Configured slot inventory</div>
                </div>

                <!-- 2. Active Placements -->
                <div class="p-5 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 shadow-md">
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Active Placements</div>
                    <div class="text-2xl font-black text-emerald-400">{{ monetization.stats.active_placements }}</div>
                    <div class="text-[11px] text-slate-400 mt-1">{{ monetization.stats.disabled_placements }} disabled</div>
                </div>

                <!-- 3. Configured Providers -->
                <div class="p-5 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 shadow-md">
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Providers Configured</div>
                    <div class="text-2xl font-black text-cyan-400">
                        {{ monetization.stats.configured_providers }} / {{ monetization.stats.total_providers }}
                    </div>
                    <div class="text-[11px] text-slate-400 mt-1">Ready for account linking</div>
                </div>

                <!-- 4. Default Provider -->
                <div class="p-5 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 shadow-md">
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Default Provider</div>
                    <div class="text-2xl font-black text-white font-mono capitalize">
                        {{ monetization.global.default_provider }}
                    </div>
                    <div class="text-[11px] text-slate-400 mt-1">Fallback ad network</div>
                </div>
            </div>

            <!-- Advertising Providers Configuration -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-white">Advertising Providers</h2>
                        <p class="text-xs text-slate-400 mt-0.5">Configure network credentials and environment parameters</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Provider 1: Google AdSense -->
                    <div class="p-6 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 shadow-lg space-y-5">
                        <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-blue-500/10 border border-blue-500/20 text-blue-400 flex items-center justify-center font-black">
                                    Ad
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-white">Google AdSense</h3>
                                    <span class="text-[11px] text-slate-400">Display, Responsive & Leaderboard Ads</span>
                                </div>
                            </div>

                            <span
                                class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                :class="adsenseForm.is_enabled && adsenseForm.publisher_id
                                    ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20'
                                    : 'bg-slate-800 text-slate-400 border border-slate-700'"
                            >
                                {{ adsenseForm.is_enabled && adsenseForm.publisher_id ? 'Configured' : 'Awaiting Approval' }}
                            </span>
                        </div>

                        <form @submit.prevent="submitAdsenseUpdate" class="space-y-4">
                            <!-- Publisher ID -->
                            <div>
                                <label for="adsense-publisher-id" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">
                                    Publisher ID
                                </label>
                                <input
                                    id="adsense-publisher-id"
                                    v-model="adsenseForm.publisher_id"
                                    type="text"
                                    placeholder="ca-pub-XXXXXXXXXXXXXXXX"
                                    class="w-full px-3.5 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-slate-200 font-mono focus:outline-none focus:border-emerald-500"
                                />
                                <p class="text-[11px] text-slate-400 mt-1">Leave empty until account approval is granted.</p>
                            </div>

                            <!-- Enable Switch & Test Mode -->
                            <div class="grid grid-cols-2 gap-4 pt-1">
                                <div class="flex items-center gap-2">
                                    <input
                                        id="adsense-enabled"
                                        v-model="adsenseForm.is_enabled"
                                        type="checkbox"
                                        class="rounded bg-slate-900 border-slate-800 text-emerald-500 focus:ring-emerald-400"
                                    />
                                    <label for="adsense-enabled" class="text-xs font-semibold text-slate-300">
                                        Provider Enabled
                                    </label>
                                </div>

                                <div class="flex items-center gap-2">
                                    <input
                                        id="adsense-test-mode"
                                        v-model="adsenseForm.test_mode"
                                        type="checkbox"
                                        class="rounded bg-slate-900 border-slate-800 text-amber-500 focus:ring-amber-400"
                                    />
                                    <label for="adsense-test-mode" class="text-xs font-semibold text-slate-300">
                                        Test Mode (Sandbox)
                                    </label>
                                </div>
                            </div>

                            <!-- Save Button -->
                            <div class="pt-2">
                                <button
                                    type="submit"
                                    :disabled="adsenseForm.processing"
                                    class="w-full px-4 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 disabled:opacity-50 text-slate-950 text-xs font-bold transition-all shadow-md shadow-emerald-500/20"
                                >
                                    {{ adsenseForm.processing ? 'Saving...' : 'Save AdSense Settings' }}
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Provider 2: H5 Games Ads -->
                    <div class="p-6 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 shadow-lg space-y-5">
                        <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center font-black">
                                    H5
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-white">H5 Games Ads</h3>
                                    <span class="text-[11px] text-slate-400">Ad Placement API & Interstitial Breaks</span>
                                </div>
                            </div>

                            <span
                                class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider text-right"
                                :class="h5Form.is_enabled && h5Form.publisher_id
                                    ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20'
                                    : 'bg-slate-800 text-slate-400 border border-slate-700'"
                            >
                                {{ monetization.providers.find(p => p.key === 'h5_games_ads')?.status_text || 'Prepared (Requires Account Approval & Game SDK)' }}
                            </span>
                        </div>

                        <!-- Architecture Info Box -->
                        <div class="p-3 rounded-xl bg-slate-950/70 border border-slate-800/80 text-[11px] text-slate-400 leading-relaxed space-y-1">
                            <div class="font-bold text-slate-300 flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-cyan-400"></span>
                                <span>Architecture Notice (Option B — Game-Document Integration):</span>
                            </div>
                            <p>
                                Google Ad Placement API requires scripts inside the game canvas document. Because TapH5 executes games inside a hardened sandbox without <code class="text-cyan-400 font-mono">allow-same-origin</code>, ad breaks must be initiated from within approved game builds. The portal provides safe non-blocking fallbacks.
                            </p>
                        </div>

                        <form @submit.prevent="submitH5Update" class="space-y-4">
                            <!-- Client ID -->
                            <div>
                                <label for="h5-client-id" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">
                                    Game Client ID
                                </label>
                                <input
                                    id="h5-client-id"
                                    v-model="h5Form.publisher_id"
                                    type="text"
                                    placeholder="ca-pub-XXXXXXXXXXXXXXXX"
                                    class="w-full px-3.5 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-slate-200 font-mono focus:outline-none focus:border-emerald-500"
                                />
                                <p class="text-[11px] text-slate-400 mt-1">Google AdSense for Games client account (ca-pub-XXXXXXXXXXXXXXXX).</p>
                            </div>

                            <!-- Enable Switch & Test Mode -->
                            <div class="grid grid-cols-2 gap-4 pt-1">
                                <div class="flex items-center gap-2">
                                    <input
                                        id="h5-enabled"
                                        v-model="h5Form.is_enabled"
                                        type="checkbox"
                                        class="rounded bg-slate-900 border-slate-800 text-emerald-500 focus:ring-emerald-400"
                                    />
                                    <label for="h5-enabled" class="text-xs font-semibold text-slate-300">
                                        Provider Enabled
                                    </label>
                                </div>

                                <div class="flex items-center gap-2">
                                    <input
                                        id="h5-test-mode"
                                        v-model="h5Form.test_mode"
                                        type="checkbox"
                                        class="rounded bg-slate-900 border-slate-800 text-amber-500 focus:ring-amber-400"
                                    />
                                    <label for="h5-test-mode" class="text-xs font-semibold text-slate-300">
                                        Test Mode (adbreak-test)
                                    </label>
                                </div>
                            </div>

                            <!-- Save Button -->
                            <div class="pt-2">
                                <button
                                    type="submit"
                                    :disabled="h5Form.processing"
                                    class="w-full px-4 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 disabled:opacity-50 text-slate-950 text-xs font-bold transition-all shadow-md shadow-emerald-500/20"
                                >
                                    {{ h5Form.processing ? 'Saving...' : 'Save H5 Games Ads Settings' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Ad Placements Inventory Table -->
            <div class="p-6 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 shadow-lg space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-white">Ad Placement Inventory</h2>
                        <p class="text-xs text-slate-400 mt-0.5">Control individual slot activation, targeting, and device visibility</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-800/80 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                                <th class="py-3 px-3">Placement & Key</th>
                                <th class="py-3 px-3">Provider</th>
                                <th class="py-3 px-3">Slot ID</th>
                                <th class="py-3 px-3">Type</th>
                                <th class="py-3 px-3 text-center">Desktop</th>
                                <th class="py-3 px-3 text-center">Mobile</th>
                                <th class="py-3 px-3 text-center">Status</th>
                                <th class="py-3 px-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/40 text-xs">
                            <tr
                                v-for="placement in monetization.placements"
                                :key="placement.id"
                                class="hover:bg-slate-900/50 transition-colors group"
                            >
                                <td class="py-3 px-3">
                                    <div class="font-bold text-white">{{ placement.name }}</div>
                                    <div class="font-mono text-[11px] text-slate-400">{{ placement.key }}</div>
                                </td>
                                <td class="py-3 px-3 font-semibold text-slate-300 capitalize">
                                    {{ placement.provider }}
                                </td>
                                <td class="py-3 px-3 font-mono text-slate-400">
                                    {{ placement.slot_id || 'Not set' }}
                                </td>
                                <td class="py-3 px-3 capitalize">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-800 text-slate-300">
                                        {{ placement.placement_type }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <span :class="placement.show_on_desktop ? 'text-emerald-400' : 'text-slate-600'">
                                        {{ placement.show_on_desktop ? '✓' : '✗' }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <span :class="placement.show_on_mobile ? 'text-emerald-400' : 'text-slate-600'">
                                        {{ placement.show_on_mobile ? '✓' : '✗' }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <span
                                        class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                        :class="placement.is_enabled
                                            ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20'
                                            : 'bg-slate-800 text-slate-400 border border-slate-700'"
                                    >
                                        {{ placement.is_enabled ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-right">
                                    <button
                                        @click="openEditPlacement(placement)"
                                        type="button"
                                        class="px-3 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold transition-colors"
                                    >
                                        Configure
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Edit Placement Modal -->
        <div
            v-if="activePlacementModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
        >
            <div class="w-full max-w-lg rounded-2xl bg-slate-900 border border-slate-800 shadow-2xl p-6 space-y-5 text-slate-200">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <div>
                        <h3 class="text-base font-bold text-white">Configure Placement</h3>
                        <p class="text-xs text-slate-400 font-mono">{{ activePlacementModal.key }}</p>
                    </div>
                    <button
                        @click="closePlacementModal"
                        type="button"
                        class="text-slate-400 hover:text-white"
                        aria-label="Close dialog"
                    >
                        ✕
                    </button>
                </div>

                <form @submit.prevent="submitPlacementUpdate" class="space-y-4 text-xs">
                    <div>
                        <label for="edit-placement-name" class="block font-bold text-slate-300 uppercase tracking-wider mb-1">
                            Placement Name
                        </label>
                        <input
                            id="edit-placement-name"
                            v-model="placementForm.name"
                            type="text"
                            class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-emerald-500"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="edit-placement-provider" class="block font-bold text-slate-300 uppercase tracking-wider mb-1">
                                Provider
                            </label>
                            <select
                                id="edit-placement-provider"
                                v-model="placementForm.provider"
                                class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-emerald-500"
                            >
                                <option value="adsense">Google AdSense</option>
                                <option value="h5_games_ads">H5 Games Ads</option>
                            </select>
                        </div>

                        <div>
                            <label for="edit-placement-type" class="block font-bold text-slate-300 uppercase tracking-wider mb-1">
                                Placement Type
                            </label>
                            <select
                                id="edit-placement-type"
                                v-model="placementForm.placement_type"
                                class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-emerald-500"
                            >
                                <option value="display">Display</option>
                                <option value="banner">Banner</option>
                                <option value="interstitial">Interstitial</option>
                                <option value="rewarded">Rewarded</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="edit-placement-slot-id" class="block font-bold text-slate-300 uppercase tracking-wider mb-1">
                            Ad Slot ID
                        </label>
                        <input
                            id="edit-placement-slot-id"
                            v-model="placementForm.slot_id"
                            type="text"
                            placeholder="e.g. 1234567890"
                            class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white font-mono focus:outline-none focus:border-emerald-500"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-4 pt-1">
                        <div>
                            <label for="edit-placement-freq" class="block font-bold text-slate-300 uppercase tracking-wider mb-1">
                                Frequency Limit (Sec)
                            </label>
                            <input
                                id="edit-placement-freq"
                                v-model="placementForm.frequency_limit"
                                type="number"
                                min="0"
                                class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white font-mono focus:outline-none focus:border-emerald-500"
                            />
                        </div>

                        <div>
                            <label for="edit-placement-priority" class="block font-bold text-slate-300 uppercase tracking-wider mb-1">
                                Priority (Order)
                            </label>
                            <input
                                id="edit-placement-priority"
                                v-model="placementForm.priority"
                                type="number"
                                min="0"
                                class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white font-mono focus:outline-none focus:border-emerald-500"
                            />
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-6 pt-2 pb-1 border-t border-slate-800/80">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                v-model="placementForm.is_enabled"
                                type="checkbox"
                                class="rounded bg-slate-950 border-slate-800 text-emerald-500 focus:ring-emerald-400"
                            />
                            <span class="font-bold text-white">Enable Placement</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                v-model="placementForm.show_on_desktop"
                                type="checkbox"
                                class="rounded bg-slate-950 border-slate-800 text-emerald-500 focus:ring-emerald-400"
                            />
                            <span>Show on Desktop</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                v-model="placementForm.show_on_mobile"
                                type="checkbox"
                                class="rounded bg-slate-950 border-slate-800 text-emerald-500 focus:ring-emerald-400"
                            />
                            <span>Show on Mobile</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
                        <button
                            @click="closePlacementModal"
                            type="button"
                            class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="placementForm.processing"
                            class="px-5 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 disabled:opacity-50 text-slate-950 text-xs font-bold transition-all shadow-md shadow-emerald-500/20"
                        >
                            {{ placementForm.processing ? 'Saving...' : 'Save Placement' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>

