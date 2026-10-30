<script setup lang="ts">
import { computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminPageHeader from '@/Components/Admin/AdminPageHeader.vue';
import FormSection from '@/Components/Admin/FormSection.vue';
import type { Category, Game, Tag } from '@/types';

const props = defineProps<{
    game: Game;
    categories: Category[];
    tags: Tag[];
    selectedTagIds: number[];
}>();

const form = useForm({
    title: props.game.title,
    slug: props.game.slug,
    description: props.game.description || '',
    instructions: props.game.instructions || '',
    category_id: props.game.category_id || '',
    thumbnail: props.game.thumbnail || '',
    is_featured: props.game.is_featured,
    is_trending: props.game.is_trending,
    is_popular: props.game.is_popular,
    seo_title: props.game.seo_title || '',
    seo_description: props.game.seo_description || '',
    tags: [...props.selectedTagIds],
});

const toggleTag = (tagId: number) => {
    const idx = form.tags.indexOf(tagId);
    if (idx > -1) {
        form.tags.splice(idx, 1);
    } else {
        form.tags.push(tagId);
    }
};

const effectiveSeoTitle = computed(() => {
    return form.seo_title.trim() || `${form.title} — Play Free Online on TapH5 Games`;
});

const effectiveSeoDescription = computed(() => {
    return form.seo_description.trim() || form.description.trim() || `Play ${form.title} for free in your browser on TapH5 Games. No downloads required.`;
});

const submit = () => {
    form.put(route('admin.games.update', props.game.id));
};
</script>

<template>
    <AdminLayout :title="`Edit Game: ${game.title}`">
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <AdminPageHeader
                :title="`Edit: ${game.title}`"
                :description="`Editing metadata and curation properties for ID #${game.id}`"
                :breadcrumbs="[
                    { label: 'Admin', href: route('admin.dashboard') },
                    { label: 'Games', href: route('admin.games.index') },
                    { label: game.title, href: route('admin.games.show', game.id) },
                    { label: 'Edit' },
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.games.show', game.id)"
                        class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 text-xs font-semibold transition-colors"
                    >
                        Cancel
                    </Link>
                    <button
                        type="button"
                        @click="submit"
                        :disabled="form.processing"
                        class="px-5 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-xs uppercase tracking-wider shadow-lg shadow-emerald-500/20 transition-all disabled:opacity-50"
                    >
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </button>
                </template>
            </AdminPageHeader>

            <!-- Form -->
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Section 1: Basic Information -->
                <FormSection
                    title="Basic Information"
                    description="Primary identifiers and descriptions surfaced on game cards and details pages."
                >
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Title -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Game Title *</label>
                            <input
                                v-model="form.title"
                                type="text"
                                required
                                class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                            />
                            <p v-if="form.errors.title" class="mt-1 text-xs text-rose-400">{{ form.errors.title }}</p>
                        </div>

                        <!-- Slug -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">URL Slug *</label>
                            <input
                                v-model="form.slug"
                                type="text"
                                required
                                class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs font-mono text-white focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                            />
                            <p class="mt-1 text-[11px] text-slate-400 font-mono">Route: /games/{{ form.slug || '...' }}</p>
                            <p v-if="form.errors.slug" class="mt-1 text-xs text-rose-400">{{ form.errors.slug }}</p>
                        </div>
                    </div>

                    <!-- Category Selector -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Primary Category</label>
                        <select
                            v-model="form.category_id"
                            class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500"
                        >
                            <option value="">None / Unassigned</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                        <p v-if="form.errors.category_id" class="mt-1 text-xs text-rose-400">{{ form.errors.category_id }}</p>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Description</label>
                        <textarea
                            v-model="form.description"
                            rows="3"
                            placeholder="Enter game summary for player overview and social previews..."
                            class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 leading-relaxed"
                        ></textarea>
                        <p v-if="form.errors.description" class="mt-1 text-xs text-rose-400">{{ form.errors.description }}</p>
                    </div>

                    <!-- Instructions -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Gameplay Instructions</label>
                        <textarea
                            v-model="form.instructions"
                            rows="3"
                            placeholder="Keyboard controls, tap gestures, objective..."
                            class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 leading-relaxed"
                        ></textarea>
                        <p v-if="form.errors.instructions" class="mt-1 text-xs text-rose-400">{{ form.errors.instructions }}</p>
                    </div>
                </FormSection>

                <!-- Section 2: Media & Thumbnail -->
                <FormSection
                    title="Thumbnail & Presentation"
                    description="Thumbnail image asset used across category listings and search grids."
                >
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 items-start">
                        <div class="sm:col-span-2 space-y-2">
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Thumbnail Path / URL</label>
                            <input
                                v-model="form.thumbnail"
                                type="text"
                                placeholder="/images/thumbnails/my-game.svg"
                                class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white font-mono focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                            />
                            <p class="text-[11px] text-slate-400">
                                Enter relative path (e.g. <code class="text-emerald-400">/images/thumbnails/...</code>) or fully qualified URL.
                            </p>
                            <p v-if="form.errors.thumbnail" class="mt-1 text-xs text-rose-400">{{ form.errors.thumbnail }}</p>
                        </div>

                        <!-- Live Thumbnail Preview -->
                        <div class="p-3 bg-slate-950/60 rounded-xl border border-slate-800/80 space-y-1.5">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Live Preview</span>
                            <div class="aspect-[4/3] rounded-lg overflow-hidden bg-slate-900 border border-slate-800 flex items-center justify-center">
                                <img
                                    :src="form.thumbnail || '/images/thumbnails/default-game.svg'"
                                    :alt="form.title"
                                    class="w-full h-full object-cover"
                                />
                            </div>
                        </div>
                    </div>
                </FormSection>

                <!-- Section 3: Curation Flags -->
                <FormSection
                    title="Curation & Promotion Flags"
                    description="Promote this game on the homepage carousel, trending lists, or popular showcases."
                >
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <!-- Featured -->
                        <label
                            class="p-4 rounded-xl border cursor-pointer transition-all flex items-start gap-3 select-none"
                            :class="form.is_featured
                                ? 'bg-purple-500/10 border-purple-500/40 text-purple-200'
                                : 'bg-slate-900/40 border-slate-800 text-slate-400 hover:border-slate-700'"
                        >
                            <input
                                v-model="form.is_featured"
                                type="checkbox"
                                class="mt-0.5 rounded bg-slate-900 border-slate-700 text-purple-500 focus:ring-purple-400"
                            />
                            <div>
                                <span class="font-bold text-xs text-white block">Featured Game</span>
                                <span class="text-[11px] text-slate-400 block mt-0.5">Showcase on top hero section</span>
                            </div>
                        </label>

                        <!-- Trending -->
                        <label
                            class="p-4 rounded-xl border cursor-pointer transition-all flex items-start gap-3 select-none"
                            :class="form.is_trending
                                ? 'bg-amber-500/10 border-amber-500/40 text-amber-200'
                                : 'bg-slate-900/40 border-slate-800 text-slate-400 hover:border-slate-700'"
                        >
                            <input
                                v-model="form.is_trending"
                                type="checkbox"
                                class="mt-0.5 rounded bg-slate-900 border-slate-700 text-amber-500 focus:ring-amber-400"
                            />
                            <div>
                                <span class="font-bold text-xs text-white block">Trending Now</span>
                                <span class="text-[11px] text-slate-400 block mt-0.5">High player engagement badge</span>
                            </div>
                        </label>

                        <!-- Popular -->
                        <label
                            class="p-4 rounded-xl border cursor-pointer transition-all flex items-start gap-3 select-none"
                            :class="form.is_popular
                                ? 'bg-cyan-500/10 border-cyan-500/40 text-cyan-200'
                                : 'bg-slate-900/40 border-slate-800 text-slate-400 hover:border-slate-700'"
                        >
                            <input
                                v-model="form.is_popular"
                                type="checkbox"
                                class="mt-0.5 rounded bg-slate-900 border-slate-700 text-cyan-500 focus:ring-cyan-400"
                            />
                            <div>
                                <span class="font-bold text-xs text-white block">Popular Catalog</span>
                                <span class="text-[11px] text-slate-400 block mt-0.5">Include in popular filters</span>
                            </div>
                        </label>
                    </div>
                </FormSection>

                <!-- Section 4: Tags Assignment -->
                <FormSection
                    title="Tags & Taxonomy"
                    description="Assign searchable keywords and descriptive tags to this game."
                >
                    <div v-if="tags.length === 0" class="text-xs text-slate-400 italic">
                        No tags created yet.
                    </div>
                    <div v-else class="flex flex-wrap gap-2">
                        <button
                            v-for="tag in tags"
                            :key="tag.id"
                            type="button"
                            @click="toggleTag(tag.id)"
                            class="px-3 py-1.5 rounded-xl text-xs font-semibold border transition-all flex items-center gap-1.5"
                            :class="form.tags.includes(tag.id)
                                ? 'bg-emerald-500 text-slate-950 border-emerald-400 shadow-sm shadow-emerald-500/20'
                                : 'bg-slate-900 text-slate-400 border-slate-800 hover:text-white hover:border-slate-700'"
                        >
                            <span>#{{ tag.name }}</span>
                            <span v-if="form.tags.includes(tag.id)" class="text-slate-950 font-black">✓</span>
                        </button>
                    </div>
                </FormSection>

                <!-- Section 5: SEO Customization & Live SERP Preview -->
                <FormSection
                    title="Search Engine Optimization (SEO)"
                    description="Configure how this game is indexed and appears in Google search engine result pages."
                >
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">SEO Title Override</label>
                            <input
                                v-model="form.seo_title"
                                type="text"
                                placeholder="Leave blank to use default portal title"
                                class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500"
                            />
                            <p v-if="form.errors.seo_title" class="mt-1 text-xs text-rose-400">{{ form.errors.seo_title }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">SEO Meta Description</label>
                            <textarea
                                v-model="form.seo_description"
                                rows="2"
                                placeholder="Leave blank to use game description"
                                class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500"
                            ></textarea>
                            <p v-if="form.errors.seo_description" class="mt-1 text-xs text-rose-400">{{ form.errors.seo_description }}</p>
                        </div>

                        <!-- Google SERP Snippet Preview -->
                        <div class="p-4 rounded-xl bg-slate-950/80 border border-slate-800 space-y-1">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1.5">
                                Google Search Snippet Preview
                            </span>
                            <p class="text-xs text-slate-400 font-mono truncate">
                                https://taph5gaming.com/games/{{ form.slug || 'game-slug' }}
                            </p>
                            <p class="text-sm font-semibold text-blue-400 hover:underline cursor-pointer truncate">
                                {{ effectiveSeoTitle }}
                            </p>
                            <p class="text-xs text-slate-400 line-clamp-2 leading-relaxed">
                                {{ effectiveSeoDescription }}
                            </p>
                        </div>
                    </div>
                </FormSection>

                <!-- Action Footer -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800/80">
                    <Link
                        :href="route('admin.games.show', game.id)"
                        class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-bold text-slate-300 transition-colors"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-xs uppercase tracking-wider shadow-lg shadow-emerald-500/20 transition-all disabled:opacity-50"
                    >
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
