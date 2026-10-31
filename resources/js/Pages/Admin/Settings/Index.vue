<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

interface SettingField {
    id: number;
    key: string;
    value: any;
    type: string;
    description: string;
}

const props = defineProps<{
    settings: Record<string, Record<string, SettingField>>;
}>();

const activeGroup = ref<'general' | 'seo' | 'gameplay' | 'analytics' | 'monetization'>('general');

// Prepare forms for each group
const generalForm = useForm({
    group: 'general',
    site_name: props.settings.general?.site_name?.value ?? 'TapH5 Games',
    site_description: props.settings.general?.site_description?.value ?? '',
    contact_email: props.settings.general?.contact_email?.value ?? '',
    default_language: props.settings.general?.default_language?.value ?? 'en',
    default_timezone: props.settings.general?.default_timezone?.value ?? 'UTC',
    social_twitter: props.settings.general?.social_twitter?.value ?? '',
    social_discord: props.settings.general?.social_discord?.value ?? '',
    social_youtube: props.settings.general?.social_youtube?.value ?? '',
});

const seoForm = useForm({
    group: 'seo',
    default_meta_title: props.settings.seo?.default_meta_title?.value ?? '',
    default_meta_description: props.settings.seo?.default_meta_description?.value ?? '',
    og_image: props.settings.seo?.og_image?.value ?? '',
    canonical_url: props.settings.seo?.canonical_url?.value ?? '',
    robots_default: props.settings.seo?.robots_default?.value ?? 'index, follow',
});

const gameplayForm = useForm({
    group: 'gameplay',
    default_games_per_page: Number(props.settings.gameplay?.default_games_per_page?.value ?? 18),
    homepage_game_limit: Number(props.settings.gameplay?.homepage_game_limit?.value ?? 6),
    related_games_limit: Number(props.settings.gameplay?.related_games_limit?.value ?? 6),
    enable_recommendations: Boolean(props.settings.gameplay?.enable_recommendations?.value ?? true),
});

const analyticsForm = useForm({
    group: 'analytics',
    analytics_enabled: Boolean(props.settings.analytics?.analytics_enabled?.value ?? true),
    data_retention_days: Number(props.settings.analytics?.data_retention_days?.value ?? 90),
    session_timeout_minutes: Number(props.settings.analytics?.session_timeout_minutes?.value ?? 30),
});

const monetizationForm = useForm({
    group: 'monetization',
    ads_enabled: Boolean(props.settings.monetization?.ads_enabled?.value ?? false),
    consent_mode_default: String(props.settings.monetization?.consent_mode_default?.value ?? 'opt-in'),
});

const submitGroup = (group: string) => {
    const form = group === 'general' ? generalForm
        : group === 'seo' ? seoForm
        : group === 'gameplay' ? gameplayForm
        : group === 'analytics' ? analyticsForm
        : monetizationForm;

    form.put(route('admin.settings.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AdminLayout title="Platform Settings">
        <div class="space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">
                    Platform Settings
                </h1>
                <p class="text-xs sm:text-sm text-slate-400 mt-1">
                    Manage runtime portal configurations, defaults, gameplay behavior, and platform policies safely.
                </p>
            </div>

            <!-- Group Tabs -->
            <div class="flex flex-wrap gap-2 p-1.5 rounded-2xl bg-slate-900 border border-slate-800">
                <button
                    type="button"
                    @click="activeGroup = 'general'"
                    :class="activeGroup === 'general' ? 'bg-emerald-500 text-slate-950 font-bold' : 'text-slate-400 hover:text-white'"
                    class="px-4 py-2 rounded-xl text-xs transition-colors"
                >
                    General
                </button>
                <button
                    type="button"
                    @click="activeGroup = 'seo'"
                    :class="activeGroup === 'seo' ? 'bg-emerald-500 text-slate-950 font-bold' : 'text-slate-400 hover:text-white'"
                    class="px-4 py-2 rounded-xl text-xs transition-colors"
                >
                    SEO Defaults
                </button>
                <button
                    type="button"
                    @click="activeGroup = 'gameplay'"
                    :class="activeGroup === 'gameplay' ? 'bg-emerald-500 text-slate-950 font-bold' : 'text-slate-400 hover:text-white'"
                    class="px-4 py-2 rounded-xl text-xs transition-colors"
                >
                    Gameplay Limits
                </button>
                <button
                    type="button"
                    @click="activeGroup = 'analytics'"
                    :class="activeGroup === 'analytics' ? 'bg-emerald-500 text-slate-950 font-bold' : 'text-slate-400 hover:text-white'"
                    class="px-4 py-2 rounded-xl text-xs transition-colors"
                >
                    Analytics & Retention
                </button>
                <button
                    type="button"
                    @click="activeGroup = 'monetization'"
                    :class="activeGroup === 'monetization' ? 'bg-emerald-500 text-slate-950 font-bold' : 'text-slate-400 hover:text-white'"
                    class="px-4 py-2 rounded-xl text-xs transition-colors"
                >
                    Monetization Policy
                </button>
            </div>

            <!-- Content Card -->
            <div class="rounded-2xl bg-slate-900 border border-slate-800 p-6 shadow-xl">
                <!-- Group 1: General Settings -->
                <form v-show="activeGroup === 'general'" @submit.prevent="submitGroup('general')" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Website Brand Name</label>
                            <input
                                v-model="generalForm.site_name"
                                type="text"
                                class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs focus:ring-1 focus:ring-emerald-400"
                                required
                            />
                            <p class="text-[11px] text-slate-500 mt-1">Displayed in title tags, header, and system notifications</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Contact Email</label>
                            <input
                                v-model="generalForm.contact_email"
                                type="email"
                                class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs focus:ring-1 focus:ring-emerald-400"
                                required
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Website Description</label>
                        <textarea
                            v-model="generalForm.site_description"
                            rows="2"
                            class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs focus:ring-1 focus:ring-emerald-400"
                            required
                        ></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Default Language Code</label>
                            <input
                                v-model="generalForm.default_language"
                                type="text"
                                class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Default Timezone</label>
                            <input
                                v-model="generalForm.default_timezone"
                                type="text"
                                class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs"
                            />
                        </div>
                    </div>

                    <div class="pt-2 border-t border-slate-800/80">
                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Community & Social Links</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-400 mb-1">X (Twitter)</label>
                                <input
                                    v-model="generalForm.social_twitter"
                                    type="url"
                                    class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs"
                                />
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-400 mb-1">Discord</label>
                                <input
                                    v-model="generalForm.social_discord"
                                    type="url"
                                    class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs"
                                />
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-400 mb-1">YouTube</label>
                                <input
                                    v-model="generalForm.social_youtube"
                                    type="url"
                                    class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-4 border-t border-slate-800">
                        <button
                            type="submit"
                            :disabled="generalForm.processing"
                            class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs shadow-lg transition-colors"
                        >
                            Save General Settings
                        </button>
                    </div>
                </form>

                <!-- Group 2: SEO Settings -->
                <form v-show="activeGroup === 'seo'" @submit.prevent="submitGroup('seo')" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Default Meta Title</label>
                        <input
                            v-model="seoForm.default_meta_title"
                            type="text"
                            class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs focus:ring-1 focus:ring-emerald-400"
                            required
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Default Meta Description</label>
                        <textarea
                            v-model="seoForm.default_meta_description"
                            rows="2"
                            class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs focus:ring-1 focus:ring-emerald-400"
                            required
                        ></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Default Open Graph Image URL</label>
                            <input
                                v-model="seoForm.og_image"
                                type="text"
                                class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs"
                                required
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Default Robots Directive</label>
                            <input
                                v-model="seoForm.robots_default"
                                type="text"
                                class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs"
                                required
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Canonical Base URL Override</label>
                        <input
                            v-model="seoForm.canonical_url"
                            type="url"
                            placeholder="https://taph5gaming.com (leave blank to auto-detect)"
                            class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs"
                        />
                    </div>

                    <div class="flex justify-end pt-4 border-t border-slate-800">
                        <button
                            type="submit"
                            :disabled="seoForm.processing"
                            class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs shadow-lg transition-colors"
                        >
                            Save SEO Settings
                        </button>
                    </div>
                </form>

                <!-- Group 3: Gameplay Settings -->
                <form v-show="activeGroup === 'gameplay'" @submit.prevent="submitGroup('gameplay')" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Games Per Page (Catalog)</label>
                            <input
                                v-model.number="gameplayForm.default_games_per_page"
                                type="number"
                                min="6"
                                max="60"
                                class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs"
                                required
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Homepage Section Limit</label>
                            <input
                                v-model.number="gameplayForm.homepage_game_limit"
                                type="number"
                                min="2"
                                max="24"
                                class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs"
                                required
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Related Games Limit</label>
                            <input
                                v-model.number="gameplayForm.related_games_limit"
                                type="number"
                                min="2"
                                max="24"
                                class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs"
                                required
                            />
                        </div>
                    </div>

                    <div class="pt-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                v-model="gameplayForm.enable_recommendations"
                                type="checkbox"
                                class="rounded bg-slate-950 border-slate-800 text-emerald-500 focus:ring-0"
                            />
                            <span class="text-xs font-semibold text-slate-300">
                                Enable Algorithmic Recommendations on Game Player Screen
                            </span>
                        </label>
                    </div>

                    <div class="flex justify-end pt-4 border-t border-slate-800">
                        <button
                            type="submit"
                            :disabled="gameplayForm.processing"
                            class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs shadow-lg transition-colors"
                        >
                            Save Gameplay Settings
                        </button>
                    </div>
                </form>

                <!-- Group 4: Analytics Settings -->
                <form v-show="activeGroup === 'analytics'" @submit.prevent="submitGroup('analytics')" class="space-y-4">
                    <div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                v-model="analyticsForm.analytics_enabled"
                                type="checkbox"
                                class="rounded bg-slate-950 border-slate-800 text-emerald-500 focus:ring-0"
                            />
                            <span class="text-xs font-semibold text-slate-300">
                                Enable First-Party Privacy-Preserving Event Ingestion
                            </span>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Data Retention Period (Days)</label>
                            <input
                                v-model.number="analyticsForm.data_retention_days"
                                type="number"
                                min="7"
                                max="365"
                                class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs"
                                required
                            />
                            <p class="text-[11px] text-slate-500 mt-1">Raw visitor events older than this can be pruned</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Session Inactivity Timeout (Minutes)</label>
                            <input
                                v-model.number="analyticsForm.session_timeout_minutes"
                                type="number"
                                min="5"
                                max="120"
                                class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs"
                                required
                            />
                        </div>
                    </div>

                    <div class="flex justify-end pt-4 border-t border-slate-800">
                        <button
                            type="submit"
                            :disabled="analyticsForm.processing"
                            class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs shadow-lg transition-colors"
                        >
                            Save Analytics Settings
                        </button>
                    </div>
                </form>

                <!-- Group 5: Monetization Settings -->
                <form v-show="activeGroup === 'monetization'" @submit.prevent="submitGroup('monetization')" class="space-y-4">
                    <div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                v-model="monetizationForm.ads_enabled"
                                type="checkbox"
                                class="rounded bg-slate-950 border-slate-800 text-emerald-500 focus:ring-0"
                            />
                            <span class="text-xs font-semibold text-slate-300">
                                Master Advertising Switch (Simulation Sandbox Active)
                            </span>
                        </label>
                        <p class="text-[11px] text-slate-500 mt-1">
                            When disabled, public pages display zero third-party ads or scripts.
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Default Consent Policy</label>
                        <select
                            v-model="monetizationForm.consent_mode_default"
                            class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs"
                        >
                            <option value="opt-in">Strict Opt-In (GDPR compliant banner required before ad loading)</option>
                            <option value="opt-out">Notice & Opt-Out (US standard)</option>
                            <option value="strict">Strict Privacy (Zero trackers permitted)</option>
                        </select>
                    </div>

                    <div class="flex justify-end pt-4 border-t border-slate-800">
                        <button
                            type="submit"
                            :disabled="monetizationForm.processing"
                            class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs shadow-lg transition-colors"
                        >
                            Save Monetization Policy
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>

