<script setup lang="ts">
import { ref, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import GameCard from '@/Components/GameCard.vue';
import Pagination from '@/Components/Pagination.vue';
import EmptyState from '@/Components/EmptyState.vue';
import SeoHead from '@/Components/SeoHead.vue';
import type { Category, Game, PaginatedData, SiteMeta } from '@/types';

const props = defineProps<{
    games: PaginatedData<Game>;
    categories: Category[];
    filters: {
        search?: string;
        category?: string;
        sort?: string;
    };
    meta: SiteMeta;
}>();

const search = ref(props.filters.search || '');
const selectedCategory = ref(props.filters.category || '');
const selectedSort = ref(props.filters.sort || 'newest');

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

const applyFilters = () => {
    router.get(
        route('games.index'),
        {
            search: search.value.trim() || undefined,
            category: selectedCategory.value || undefined,
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

const setCategory = (catSlug: string) => {
    selectedCategory.value = selectedCategory.value === catSlug ? '' : catSlug;
    applyFilters();
};

const setSort = (sortValue: string) => {
    selectedSort.value = sortValue;
    applyFilters();
};

const clearFilters = () => {
    search.value = '';
    selectedCategory.value = '';
    selectedSort.value = 'newest';
    router.get(route('games.index'));
};
</script>

<template>
    <SeoHead :meta="meta" />

    <AppLayout>
        <!-- Header & Breadcrumbs -->
        <div class="mb-8">
            <nav class="flex items-center gap-2 text-xs text-slate-400 mb-3">
                <Link href="/" class="hover:text-emerald-400 transition-colors">Home</Link>
                <span>/</span>
                <span class="text-slate-200 font-medium">All Games</span>
            </nav>

            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div>
                    <h1 class="text-3xl sm:text-4xl font-black text-white tracking-tight">
                        {{ selectedCategory ? `${categories.find(c => c.slug === selectedCategory)?.name || ''} Games` : 'All HTML5 Games' }}
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">
                        Showing {{ games.total }} free games ready for instant in-browser play.
                    </p>
                </div>

                <!-- Sorting Select -->
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs text-slate-400 font-medium hidden sm:inline">Sort by:</span>
                    <select
                        v-model="selectedSort"
                        @change="applyFilters"
                        class="px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-slate-200 focus:outline-none focus:border-emerald-500 font-semibold"
                    >
                        <option value="newest">Newest First</option>
                        <option value="popular">Most Popular</option>
                        <option value="featured">Featured First</option>
                        <option value="alpha">Alphabetical (A-Z)</option>
                        <option value="oldest">Oldest First</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Filter & Search Controls Bar -->
        <div class="flex flex-col gap-4 mb-8 p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80">
            <!-- Search bar -->
            <div class="relative w-full">
                <input
                    v-model="search"
                    @input="handleSearchInput"
                    type="text"
                    placeholder="Search games by title or keywords..."
                    class="w-full pl-10 pr-10 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-xs sm:text-sm text-slate-200 placeholder-slate-400 focus:outline-none focus:border-emerald-500 transition-all"
                />
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                    </svg>
                </span>
                <button
                    v-if="search"
                    @click="search = ''; applyFilters();"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white"
                >
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" x2="6" y1="6" y2="18"/><line x1="6" x2="18" y1="6" y2="18"/>
                    </svg>
                </button>
            </div>

            <!-- Category Pills Filter -->
            <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none">
                <button
                    @click="setCategory('')"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-all focus:outline-none"
                    :class="!selectedCategory ? 'bg-emerald-500 text-slate-950 font-bold shadow-sm shadow-emerald-500/20' : 'bg-slate-800/60 text-slate-300 hover:bg-slate-800 border border-slate-700/60'"
                >
                    All Genres
                </button>
                <button
                    v-for="cat in categories"
                    :key="cat.id"
                    @click="setCategory(cat.slug)"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-all focus:outline-none"
                    :class="selectedCategory === cat.slug ? 'bg-emerald-500 text-slate-950 font-bold shadow-sm shadow-emerald-500/20' : 'bg-slate-800/60 text-slate-300 hover:bg-slate-800 border border-slate-700/60'"
                >
                    {{ cat.name }}
                </button>
            </div>
        </div>

        <!-- Games Grid -->
        <div v-if="games.data.length > 0" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
            <GameCard
                v-for="game in games.data"
                :key="game.id"
                :game="game"
            />
        </div>

        <!-- Empty State -->
        <EmptyState
            v-else
            title="No Matching Games Found"
            message="We couldn't find any games matching your current search or category filter. Try clearing filters to see all available games."
            action-text="Reset Filters"
            @click="clearFilters"
        />

        <!-- Pagination -->
        <Pagination :links="games.links" />
    </AppLayout>
</template>

