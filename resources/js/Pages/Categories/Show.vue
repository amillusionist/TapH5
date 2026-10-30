<script setup lang="ts">
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import GameCard from '@/Components/GameCard.vue';
import Pagination from '@/Components/Pagination.vue';
import EmptyState from '@/Components/EmptyState.vue';
import SeoHead from '@/Components/SeoHead.vue';
import type { Category, Game, PaginatedData, SiteMeta } from '@/types';

const props = defineProps<{
    category: Category;
    games: PaginatedData<Game>;
    filters: {
        search?: string;
        sort?: string;
    };
    meta: SiteMeta;
}>();

const search = ref(props.filters.search || '');
const selectedSort = ref(props.filters.sort || 'newest');

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

const applyFilters = () => {
    router.get(
        route('categories.show', { slug: props.category.slug }),
        {
            search: search.value.trim() || undefined,
            sort: selectedSort.value !== 'newest' ? selectedSort.value : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

const handleSearchInput = () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 350);
};

const clearFilters = () => {
    search.value = '';
    selectedSort.value = 'newest';
    router.get(route('categories.show', { slug: props.category.slug }));
};
</script>

<template>
    <SeoHead :meta="meta" />

    <AppLayout>
        <!-- Breadcrumbs -->
        <nav class="flex items-center gap-2 text-xs text-slate-400 mb-4">
            <Link href="/" class="hover:text-emerald-400 transition-colors">Home</Link>
            <span>/</span>
            <Link :href="route('games.index')" class="hover:text-emerald-400 transition-colors">Categories</Link>
            <span>/</span>
            <span class="text-slate-200 font-medium">{{ category.name }}</span>
        </nav>

        <!-- Category Header Banner -->
        <div class="relative overflow-hidden rounded-3xl bg-slate-900 border border-slate-800 p-8 sm:p-12 mb-8">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
                <div class="max-w-2xl">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-semibold uppercase tracking-wider mb-3">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                        Category Spotlight
                    </div>

                    <h1 class="text-3xl sm:text-4xl md:text-5xl font-black text-white tracking-tight">
                        {{ category.name }} Games
                    </h1>

                    <p class="text-xs sm:text-sm text-slate-300 mt-2 leading-relaxed">
                        {{ category.description || `Browse and play free online ${category.name} games on TapH5.` }}
                    </p>
                </div>

                <div class="shrink-0 flex items-center gap-3">
                    <div class="px-5 py-3 rounded-2xl bg-slate-950/80 border border-slate-800 text-center">
                        <span class="block text-2xl font-black text-emerald-400">{{ games.total }}</span>
                        <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Total Games</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter & Search Controls Bar -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mb-8 p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80">
            <!-- Search bar -->
            <div class="relative w-full sm:max-w-md">
                <label for="category-search-input" class="sr-only">Search in {{ category.name }} games</label>
                <input
                    id="category-search-input"
                    v-model="search"
                    @input="handleSearchInput"
                    type="text"
                    :aria-label="`Search in ${category.name} games`"
                    :placeholder="`Search in ${category.name} games...`"
                    class="w-full pl-10 pr-10 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-xs sm:text-sm text-slate-200 placeholder-slate-400 focus:outline-none focus:border-emerald-500 transition-all"
                />
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" aria-hidden="true">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                    </svg>
                </span>
                <button
                    v-if="search"
                    @click="search = ''; applyFilters();"
                    aria-label="Clear search input"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white p-1 rounded focus:outline-none focus:ring-1 focus:ring-emerald-400"
                >
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <line x1="18" x2="6" y1="6" y2="18"/><line x1="6" x2="18" y1="6" y2="18"/>
                    </svg>
                </button>
            </div>

            <!-- Sorting Select -->
            <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                <label for="category-sort-select" class="text-xs text-slate-400 font-medium hidden sm:inline">Sort:</label>
                <select
                    id="category-sort-select"
                    v-model="selectedSort"
                    @change="applyFilters"
                    aria-label="Sort category games"
                    class="px-3.5 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-slate-200 focus:outline-none focus:border-emerald-500 font-semibold"
                >
                    <option value="newest">Newest First</option>
                    <option value="popular">Most Popular</option>
                    <option value="featured">Featured First</option>
                    <option value="alpha">Alphabetical (A-Z)</option>
                    <option value="oldest">Oldest First</option>
                </select>
            </div>
        </div>

        <!-- Games Grid -->
        <div v-if="games.data.length > 0" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
            <GameCard
                v-for="(game, idx) in games.data"
                :key="game.id"
                :game="game"
                :priority="idx < 6"
            />
        </div>

        <!-- Empty State -->
        <EmptyState
            v-else
            :title="`No ${category.name} Games Found`"
            message="No games in this category match your search criteria. Try modifying your search or check other categories."
            action-text="Reset Filters"
            @click="clearFilters"
        />

        <!-- Pagination -->
        <Pagination :links="games.links" />
    </AppLayout>
</template>

