<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
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

const submit = () => {
    form.put(route('admin.games.update', props.game.id));
};
</script>

<template>
    <AdminLayout :title="`Edit Game: ${game.title}`">
        <div class="max-w-4xl space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between pb-4 border-b border-slate-800/80">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('admin.games.show', game.id)"
                        class="p-2 rounded-xl bg-slate-900 border border-slate-800 text-slate-400 hover:text-white transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M19 12H5M12 19l-7-7 7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </Link>
                    <div>
                        <h2 class="text-xl font-extrabold text-white">Edit Game Metadata</h2>
                        <p class="text-xs text-slate-400 mt-0.5">Editing #{{ game.id }} ({{ game.title }})</p>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Basic Metadata Card -->
                <div class="p-6 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 space-y-4">
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider">Basic Metadata</h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Title -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Title *</label>
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
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Slug *</label>
                            <input
                                v-model="form.slug"
                                type="text"
                                required
                                class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                            />
                            <p v-if="form.errors.slug" class="mt-1 text-xs text-rose-400">{{ form.errors.slug }}</p>
                        </div>
                    </div>

                    <!-- Category -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Category</label>
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
                            class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                        ></textarea>
                        <p v-if="form.errors.description" class="mt-1 text-xs text-rose-400">{{ form.errors.description }}</p>
                    </div>

                    <!-- Instructions -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Gameplay Instructions</label>
                        <textarea
                            v-model="form.instructions"
                            rows="3"
                            class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                        ></textarea>
                        <p v-if="form.errors.instructions" class="mt-1 text-xs text-rose-400">{{ form.errors.instructions }}</p>
                    </div>

                    <!-- Thumbnail Path -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Thumbnail Path / URL</label>
                        <input
                            v-model="form.thumbnail"
                            type="text"
                            placeholder="/images/thumbnails/my-game.svg"
                            class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                        />
                        <p v-if="form.errors.thumbnail" class="mt-1 text-xs text-rose-400">{{ form.errors.thumbnail }}</p>
                    </div>
                </div>

                <!-- Curation & Flags Card -->
                <div class="p-6 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 space-y-4">
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider">Curation Flags</h3>

                    <div class="flex flex-wrap gap-6 text-xs">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                v-model="form.is_featured"
                                type="checkbox"
                                class="rounded bg-slate-900 border-slate-700 text-emerald-500 focus:ring-emerald-400"
                            />
                            <span class="font-semibold text-slate-200">Featured Game</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                v-model="form.is_trending"
                                type="checkbox"
                                class="rounded bg-slate-900 border-slate-700 text-amber-500 focus:ring-amber-400"
                            />
                            <span class="font-semibold text-slate-200">Trending</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                v-model="form.is_popular"
                                type="checkbox"
                                class="rounded bg-slate-900 border-slate-700 text-cyan-500 focus:ring-cyan-400"
                            />
                            <span class="font-semibold text-slate-200">Popular</span>
                        </label>
                    </div>
                </div>

                <!-- Tags Assignment Card -->
                <div class="p-6 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 space-y-4">
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider">Tags Assignment</h3>

                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="tag in tags"
                            :key="tag.id"
                            type="button"
                            @click="toggleTag(tag.id)"
                            class="px-3 py-1.5 rounded-xl text-xs font-semibold border transition-all"
                            :class="form.tags.includes(tag.id) ? 'bg-emerald-500 text-slate-950 border-emerald-400' : 'bg-slate-900 text-slate-400 border-slate-800 hover:text-white'"
                        >
                            #{{ tag.name }}
                        </button>
                    </div>
                </div>

                <!-- SEO Metadata Card -->
                <div class="p-6 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 space-y-4">
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider">SEO Customization</h3>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">SEO Title Override</label>
                        <input
                            v-model="form.seo_title"
                            type="text"
                            placeholder="Defaults to auto-generated portal title"
                            class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500"
                        />
                        <p v-if="form.errors.seo_title" class="mt-1 text-xs text-rose-400">{{ form.errors.seo_title }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">SEO Description Override</label>
                        <textarea
                            v-model="form.seo_description"
                            rows="2"
                            placeholder="Defaults to auto-generated portal description"
                            class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500"
                        ></textarea>
                        <p v-if="form.errors.seo_description" class="mt-1 text-xs text-rose-400">{{ form.errors.seo_description }}</p>
                    </div>
                </div>

                <!-- Action Footer -->
                <div class="flex items-center justify-end gap-3 pt-2">
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

