<script setup lang="ts">
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type { HomepageSection, HomepageSlide, Game } from '@/types';

const props = defineProps<{
    sections: HomepageSection[];
    slides: HomepageSlide[];
    availableGames: Game[];
}>();

// Editing Section State
const activeTab = ref<'sections' | 'hero'>('sections');
const editingSection = ref<HomepageSection | null>(null);

const sectionForm = useForm<{
    title: string;
    subtitle: string;
    is_enabled: boolean;
    sort_order: number;
    items_limit: number;
    curated_game_ids: number[];
}>({
    title: '',
    subtitle: '',
    is_enabled: true,
    sort_order: 0,
    items_limit: 6,
    curated_game_ids: [],
});

const openEditSection = (section: HomepageSection) => {
    editingSection.value = section;
    sectionForm.title = section.title;
    sectionForm.subtitle = section.subtitle || '';
    sectionForm.is_enabled = section.is_enabled;
    sectionForm.sort_order = section.sort_order;
    sectionForm.items_limit = section.items_limit;
    sectionForm.curated_game_ids = section.curated_game_ids ? [...section.curated_game_ids] : [];
};

const closeEditSection = () => {
    editingSection.value = null;
    sectionForm.reset();
};

const submitSectionForm = () => {
    if (!editingSection.value) return;
    sectionForm.put(route('admin.homepage.sections.update', editingSection.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeEditSection();
        },
    });
};

const toggleSectionEnabled = (section: HomepageSection) => {
    router.put(route('admin.homepage.sections.update', section.id), {
        title: section.title,
        subtitle: section.subtitle,
        is_enabled: !section.is_enabled,
        sort_order: section.sort_order,
        items_limit: section.items_limit,
        curated_game_ids: section.curated_game_ids,
    }, { preserveScroll: true });
};

// Section Reordering
const moveSection = (index: number, direction: 'up' | 'down') => {
    const targetIndex = direction === 'up' ? index - 1 : index + 1;
    if (targetIndex < 0 || targetIndex >= props.sections.length) return;

    const reordered = [...props.sections];
    const [moved] = reordered.splice(index, 1);
    reordered.splice(targetIndex, 0, moved);

    const orderedIds = reordered.map(s => s.id);

    router.post(route('admin.homepage.sections.reorder'), {
        ordered_ids: orderedIds,
    }, { preserveScroll: true });
};

// Slide Management State
const editingSlide = ref<HomepageSlide | null>(null);
const isCreatingSlide = ref(false);

const slideForm = useForm<{
    title: string;
    subtitle: string;
    cta_text: string;
    cta_url: string;
    background_image: string;
    game_id: number | null;
    is_enabled: boolean;
    sort_order: number;
}>({
    title: '',
    subtitle: '',
    cta_text: 'Play Now',
    cta_url: '',
    background_image: '',
    game_id: null,
    is_enabled: true,
    sort_order: 0,
});

const openCreateSlide = () => {
    editingSlide.value = null;
    isCreatingSlide.value = true;
    slideForm.reset();
    slideForm.sort_order = props.slides.length + 1;
    slideForm.cta_text = 'Play Now';
    slideForm.is_enabled = true;
};

const openEditSlide = (slide: HomepageSlide) => {
    isCreatingSlide.value = false;
    editingSlide.value = slide;
    slideForm.title = slide.title;
    slideForm.subtitle = slide.subtitle || '';
    slideForm.cta_text = slide.cta_text || 'Play Now';
    slideForm.cta_url = slide.cta_url || '';
    slideForm.background_image = slide.background_image || '';
    slideForm.game_id = slide.game_id;
    slideForm.is_enabled = slide.is_enabled;
    slideForm.sort_order = slide.sort_order;
};

const closeSlideModal = () => {
    editingSlide.value = null;
    isCreatingSlide.value = false;
    slideForm.reset();
};

const submitSlideForm = () => {
    if (isCreatingSlide.value) {
        slideForm.post(route('admin.homepage.slides.store'), {
            preserveScroll: true,
            onSuccess: () => closeSlideModal(),
        });
    } else if (editingSlide.value) {
        slideForm.put(route('admin.homepage.slides.update', editingSlide.value.id), {
            preserveScroll: true,
            onSuccess: () => closeSlideModal(),
        });
    }
};

const deleteSlide = (slide: HomepageSlide) => {
    if (!confirm(`Are you sure you want to delete slide '${slide.title}'?`)) return;
    router.delete(route('admin.homepage.slides.destroy', slide.id), {
        preserveScroll: true,
    });
};

const toggleSlideGameSelection = (gameId: number) => {
    slideForm.game_id = slideForm.game_id === gameId ? null : gameId;
    if (slideForm.game_id) {
        const selected = props.availableGames.find(g => g.id === gameId);
        if (selected) {
            if (!slideForm.title) slideForm.title = selected.title;
            if (!slideForm.cta_url) slideForm.cta_url = `/games/${selected.slug}`;
            if (!slideForm.background_image && selected.thumbnail) slideForm.background_image = selected.thumbnail;
        }
    }
};

const toggleCuratedGame = (gameId: number) => {
    const idx = sectionForm.curated_game_ids.indexOf(gameId);
    if (idx > -1) {
        sectionForm.curated_game_ids.splice(idx, 1);
    } else {
        sectionForm.curated_game_ids.push(gameId);
    }
};
</script>

<template>
    <AdminLayout title="Homepage Content Management">
        <div class="space-y-6">
            <!-- Header Row -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">
                        Homepage Content Editor
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">
                        Configure public homepage sections, hero carousel slides, display limits, and manual game curation.
                    </p>
                </div>

                <!-- Tab Switcher -->
                <div class="flex items-center gap-1 p-1 rounded-xl bg-slate-900 border border-slate-800 shrink-0">
                    <button
                        @click="activeTab = 'sections'"
                        :class="activeTab === 'sections' ? 'bg-emerald-500 text-slate-950 font-bold' : 'text-slate-400 hover:text-white'"
                        class="px-4 py-2 rounded-lg text-xs transition-colors"
                    >
                        Content Sections ({{ sections.length }})
                    </button>
                    <button
                        @click="activeTab = 'hero'"
                        :class="activeTab === 'hero' ? 'bg-emerald-500 text-slate-950 font-bold' : 'text-slate-400 hover:text-white'"
                        class="px-4 py-2 rounded-lg text-xs transition-colors"
                    >
                        Hero Slides ({{ slides.length }})
                    </button>
                </div>
            </div>

            <!-- Tab 1: Content Sections -->
            <div v-show="activeTab === 'sections'" class="space-y-4">
                <div class="rounded-2xl bg-slate-900/90 border border-slate-800 overflow-hidden shadow-xl">
                    <div class="px-5 py-3.5 border-b border-slate-800 bg-slate-950/60 flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-300 uppercase tracking-wider">
                            Homepage Display Order & Visibility
                        </span>
                        <span class="text-xs text-slate-400">
                            Move sections up or down to reorder the public layout
                        </span>
                    </div>

                    <div class="divide-y divide-slate-800/80">
                        <div
                            v-for="(sec, idx) in sections"
                            :key="sec.id"
                            class="p-4 sm:p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:bg-slate-800/30 transition-colors"
                        >
                            <div class="flex items-start gap-4">
                                <span class="w-8 h-8 rounded-xl bg-slate-800 text-slate-300 font-black text-xs flex items-center justify-center shrink-0">
                                    {{ idx + 1 }}
                                </span>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-bold text-sm sm:text-base text-white">
                                            {{ sec.title }}
                                        </h3>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold uppercase tracking-wider bg-slate-800 text-slate-300">
                                            {{ sec.section_key }}
                                        </span>
                                        <span
                                            :class="sec.is_enabled ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' : 'bg-slate-800 text-slate-500 border-slate-700'"
                                            class="px-2 py-0.5 rounded-md text-[10px] font-bold border uppercase tracking-wider"
                                        >
                                            {{ sec.is_enabled ? 'Active' : 'Disabled' }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-400 mt-1 max-w-xl">
                                        {{ sec.subtitle || 'No subtitle configured' }}
                                    </p>
                                    <div class="flex items-center gap-4 mt-2 text-[11px] text-slate-400">
                                        <span>Display Limit: <strong>{{ sec.items_limit || 'All' }}</strong> games</span>
                                        <span v-if="sec.curated_game_ids && sec.curated_game_ids.length > 0" class="text-emerald-400 font-medium">
                                            Curated: <strong>{{ sec.curated_game_ids.length }}</strong> games selected
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-2 self-end md:self-center shrink-0">
                                <!-- Move Up -->
                                <button
                                    type="button"
                                    :disabled="idx === 0"
                                    @click="moveSection(idx, 'up')"
                                    title="Move section up"
                                    class="p-2 rounded-lg bg-slate-800 text-slate-300 hover:text-white disabled:opacity-30 disabled:pointer-events-none transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path d="m18 15-6-6-6 6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                                <!-- Move Down -->
                                <button
                                    type="button"
                                    :disabled="idx === sections.length - 1"
                                    @click="moveSection(idx, 'down')"
                                    title="Move section down"
                                    class="p-2 rounded-lg bg-slate-800 text-slate-300 hover:text-white disabled:opacity-30 disabled:pointer-events-none transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path d="m6 9 6 6 6-6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                                <!-- Toggle Enabled -->
                                <button
                                    type="button"
                                    @click="toggleSectionEnabled(sec)"
                                    :class="sec.is_enabled ? 'bg-amber-500/10 text-amber-400 border-amber-500/30 hover:bg-amber-500/20' : 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30 hover:bg-emerald-500/20'"
                                    class="px-3 py-1.5 rounded-lg text-xs font-bold border transition-colors"
                                >
                                    {{ sec.is_enabled ? 'Disable' : 'Enable' }}
                                </button>
                                <!-- Edit Modal -->
                                <button
                                    type="button"
                                    @click="openEditSection(sec)"
                                    class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs border border-slate-700 transition-colors"
                                >
                                    Configure
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Hero Slides -->
            <div v-show="activeTab === 'hero'" class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-white">Hero Showcase Slider</h2>
                        <p class="text-xs text-slate-400">Custom slides take precedence over automated featured games fallback</p>
                    </div>
                    <button
                        type="button"
                        @click="openCreateSlide"
                        class="px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs flex items-center gap-1.5 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <line x1="12" y1="5" x2="12" y2="19" stroke-width="2" stroke-linecap="round"/>
                            <line x1="5" y1="12" x2="19" y2="12" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <span>Add Slide</span>
                    </button>
                </div>

                <div v-if="slides.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="slide in slides"
                        :key="slide.id"
                        class="rounded-2xl bg-slate-900 border border-slate-800 overflow-hidden flex flex-col justify-between shadow-lg"
                    >
                        <div class="relative aspect-video bg-slate-950 overflow-hidden">
                            <img
                                :src="slide.background_image || slide.game?.thumbnail || '/images/thumbnails/default-game.svg'"
                                :alt="slide.title"
                                class="w-full h-full object-cover"
                            />
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/40 to-transparent"></div>
                            <span
                                :class="slide.is_enabled ? 'bg-emerald-500/90 text-slate-950' : 'bg-slate-800 text-slate-400'"
                                class="absolute top-2 right-2 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider shadow"
                            >
                                {{ slide.is_enabled ? 'Active' : 'Disabled' }}
                            </span>
                            <div class="absolute bottom-2 left-3 right-3">
                                <h4 class="font-bold text-sm text-white truncate">{{ slide.title }}</h4>
                                <p class="text-[11px] text-slate-300 truncate">{{ slide.subtitle || 'No subtitle' }}</p>
                            </div>
                        </div>

                        <div class="p-3 bg-slate-950/50 flex items-center justify-between text-xs">
                            <span class="text-slate-400">Order: <strong>{{ slide.sort_order }}</strong></span>
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    @click="openEditSlide(slide)"
                                    class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 font-semibold"
                                >
                                    Edit
                                </button>
                                <button
                                    type="button"
                                    @click="deleteSlide(slide)"
                                    class="px-2.5 py-1 rounded-lg bg-rose-500/10 text-rose-400 hover:bg-rose-500/20 font-semibold"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="rounded-2xl bg-slate-900/40 border border-slate-800/80 p-8 text-center text-slate-400 text-xs">
                    No custom hero slides currently defined. The homepage hero automatically showcases the top 3 featured published games.
                </div>
            </div>

            <!-- Edit Section Modal -->
            <div
                v-if="editingSection"
                class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto"
                @click.self="closeEditSection"
            >
                <div class="w-full max-w-xl rounded-2xl bg-slate-900 border border-slate-800 shadow-2xl p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <h3 class="text-base font-bold text-white">
                            Configure Section: {{ editingSection.title }}
                        </h3>
                        <button @click="closeEditSection" class="text-slate-400 hover:text-white">&times;</button>
                    </div>

                    <form @submit.prevent="submitSectionForm" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Section Title</label>
                            <input
                                v-model="sectionForm.title"
                                type="text"
                                class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs focus:ring-1 focus:ring-emerald-400"
                                required
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Subtitle / Description</label>
                            <input
                                v-model="sectionForm.subtitle"
                                type="text"
                                class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs focus:ring-1 focus:ring-emerald-400"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1">Items Limit</label>
                                <input
                                    v-model.number="sectionForm.items_limit"
                                    type="number"
                                    min="0"
                                    max="30"
                                    class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs"
                                />
                            </div>
                            <div class="flex items-center gap-2 pt-6">
                                <input
                                    id="sec-enabled"
                                    v-model="sectionForm.is_enabled"
                                    type="checkbox"
                                    class="rounded bg-slate-950 border-slate-800 text-emerald-500 focus:ring-0"
                                />
                                <label for="sec-enabled" class="text-xs font-semibold text-slate-300">Section Enabled</label>
                            </div>
                        </div>

                        <!-- Manual Game Curation Picker -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">
                                Curate Games (Optional manual override)
                            </label>
                            <p class="text-[11px] text-slate-500 mb-2">
                                When selected, these exact games will appear instead of the automated query.
                            </p>
                            <div class="max-h-48 overflow-y-auto space-y-1 p-2 rounded-xl bg-slate-950 border border-slate-800">
                                <label
                                    v-for="game in availableGames"
                                    :key="game.id"
                                    class="flex items-center justify-between p-2 rounded-lg hover:bg-slate-900 cursor-pointer text-xs"
                                >
                                    <span class="flex items-center gap-2">
                                        <input
                                            type="checkbox"
                                            :checked="sectionForm.curated_game_ids.includes(game.id)"
                                            @change="toggleCuratedGame(game.id)"
                                            class="rounded bg-slate-900 border-slate-800 text-emerald-500 focus:ring-0"
                                        />
                                        <span class="text-slate-200">{{ game.title }}</span>
                                    </span>
                                    <span class="text-[10px] text-slate-500">{{ game.category?.name }}</span>
                                </label>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
                            <button
                                type="button"
                                @click="closeEditSection"
                                class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="sectionForm.processing"
                                class="px-5 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs"
                            >
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Create / Edit Slide Modal -->
            <div
                v-if="isCreatingSlide || editingSlide"
                class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto"
                @click.self="closeSlideModal"
            >
                <div class="w-full max-w-lg rounded-2xl bg-slate-900 border border-slate-800 shadow-2xl p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <h3 class="text-base font-bold text-white">
                            {{ isCreatingSlide ? 'Add Custom Hero Slide' : 'Edit Hero Slide' }}
                        </h3>
                        <button @click="closeSlideModal" class="text-slate-400 hover:text-white">&times;</button>
                    </div>

                    <form @submit.prevent="submitSlideForm" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Slide Title</label>
                            <input
                                v-model="slideForm.title"
                                type="text"
                                class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs focus:ring-1 focus:ring-emerald-400"
                                required
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Subtitle</label>
                            <input
                                v-model="slideForm.subtitle"
                                type="text"
                                class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1">CTA Text</label>
                                <input
                                    v-model="slideForm.cta_text"
                                    type="text"
                                    class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1">CTA Destination URL</label>
                                <input
                                    v-model="slideForm.cta_url"
                                    type="text"
                                    placeholder="/games/slug"
                                    class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Background Image URL</label>
                            <input
                                v-model="slideForm.background_image"
                                type="text"
                                placeholder="/images/thumbnails/game.svg"
                                class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Associate Game (Optional)</label>
                            <select
                                :value="slideForm.game_id || ''"
                                @change="toggleSlideGameSelection(Number(($event.target as HTMLSelectElement).value))"
                                class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs"
                            >
                                <option value="">-- No Game Link --</option>
                                <option v-for="g in availableGames" :key="g.id" :value="g.id">
                                    {{ g.title }} ({{ g.category?.name }})
                                </option>
                            </select>
                        </div>

                        <div class="flex items-center gap-4 pt-2">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input
                                    v-model="slideForm.is_enabled"
                                    type="checkbox"
                                    class="rounded bg-slate-950 border-slate-800 text-emerald-500 focus:ring-0"
                                />
                                <span class="text-xs font-semibold text-slate-300">Slide Active</span>
                            </label>

                            <div class="flex items-center gap-2">
                                <span class="text-xs text-slate-400">Order:</span>
                                <input
                                    v-model.number="slideForm.sort_order"
                                    type="number"
                                    min="0"
                                    class="w-16 px-2 py-1 rounded-lg bg-slate-950 border border-slate-800 text-white text-xs"
                                />
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
                            <button
                                type="button"
                                @click="closeSlideModal"
                                class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="slideForm.processing"
                                class="px-5 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs"
                            >
                                {{ isCreatingSlide ? 'Create Slide' : 'Save Changes' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

