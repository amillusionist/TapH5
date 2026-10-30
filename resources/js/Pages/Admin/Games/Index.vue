<script setup lang="ts">
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminPageHeader from '@/Components/Admin/AdminPageHeader.vue';
import StatusBadge from '@/Components/Admin/StatusBadge.vue';
import Pagination from '@/Components/Pagination.vue';
import type { Category, Game, PaginatedData } from '@/types';

const props = defineProps<{
    games: PaginatedData<Game>;
    categories: Category[];
    filters: {
        search?: string;
        status?: string;
        category?: string;
        game_type?: string;
        sort?: string;
    };
    statusCounts: {
        all: number;
        published: number;
        draft: number;
        archived: number;
    };
}>();

const search = ref(props.filters.search || '');
const category = ref(props.filters.category || '');
const status = ref(props.filters.status || '');
const sort = ref(props.filters.sort || 'newest');

const applyFilters = () => {
    router.get(
        route('admin.games.index'),
        {
            search: search.value || undefined,
            status: status.value || undefined,
            category: category.value || undefined,
            sort: sort.value || undefined,
        },
        { preserveState: true, replace: true }
    );
};

const filterByStatus = (statusValue: string) => {
    status.value = statusValue;
    applyFilters();
};

const resetFilters = () => {
    search.value = '';
    category.value = '';
    status.value = '';
    sort.value = 'newest';
    applyFilters();
};

const hasActiveFilters = () => {
    return !!(search.value || category.value || status.value || (sort.value && sort.value !== 'newest'));
};
</script>

<template>
    <AdminLayout title="Games Management">
        <div class="space-y-6 max-w-7xl mx-auto">
            <!-- Header -->
            <AdminPageHeader
                title="Games Catalog Manager"
                description="Review, inspect, edit, publish, and manage all HTML5 games on the platform."
                :breadcrumbs="[{ label: 'Admin', href: route('admin.dashboard') }, { label: 'Games' }]"
            >
                <template #actions>
                    <Link
                        v-if="statusCounts.draft > 0"
                        :href="route('admin.games.index', { status: 'draft' })"
                        class="px-4 py-2 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 border border-amber-500/30 text-xs font-bold uppercase tracking-wider transition-all flex items-center gap-2"
                    >
                        <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                        <span>Drafts ({{ statusCounts.draft }})</span>
                    </Link>
                </template>
            </AdminPageHeader>

            <!-- Status Tabs -->
            <div class="flex flex-wrap items-center gap-2">
                <button
                    @click="filterByStatus('')"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2"
                    :class="!status
                        ? 'bg-emerald-500 text-slate-950 shadow-md shadow-emerald-500/20'
                        : 'bg-slate-900 text-slate-400 hover:text-white hover:bg-slate-800 border border-slate-800'"
                >
                    <span>All Games</span>
                    <span
                        class="px-1.5 py-0.5 rounded-md text-[10px]"
                        :class="!status ? 'bg-slate-950/20 text-slate-950 font-black' : 'bg-slate-800 text-slate-400'"
                    >
                        {{ statusCounts.all }}
                    </span>
                </button>

                <button
                    @click="filterByStatus('draft')"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2"
                    :class="status === 'draft'
                        ? 'bg-amber-400 text-slate-950 shadow-md shadow-amber-500/20'
                        : 'bg-slate-900 text-slate-400 hover:text-white hover:bg-slate-800 border border-slate-800'"
                >
                    <span>Draft Queue</span>
                    <span
                        class="px-1.5 py-0.5 rounded-md text-[10px]"
                        :class="status === 'draft' ? 'bg-slate-950/20 text-slate-950 font-black' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20'"
                    >
                        {{ statusCounts.draft }}
                    </span>
                </button>

                <button
                    @click="filterByStatus('published')"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2"
                    :class="status === 'published'
                        ? 'bg-emerald-500 text-slate-950 shadow-md shadow-emerald-500/20'
                        : 'bg-slate-900 text-slate-400 hover:text-white hover:bg-slate-800 border border-slate-800'"
                >
                    <span>Published</span>
                    <span
                        class="px-1.5 py-0.5 rounded-md text-[10px]"
                        :class="status === 'published' ? 'bg-slate-950/20 text-slate-950 font-black' : 'bg-slate-800 text-slate-400'"
                    >
                        {{ statusCounts.published }}
                    </span>
                </button>

                <button
                    @click="filterByStatus('archived')"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2"
                    :class="status === 'archived'
                        ? 'bg-slate-600 text-white shadow-md'
                        : 'bg-slate-900 text-slate-400 hover:text-white hover:bg-slate-800 border border-slate-800'"
                >
                    <span>Archived</span>
                    <span
                        class="px-1.5 py-0.5 rounded-md text-[10px]"
                        :class="status === 'archived' ? 'bg-slate-950/20 text-white font-black' : 'bg-slate-800 text-slate-400'"
                    >
                        {{ statusCounts.archived }}
                    </span>
                </button>
            </div>

            <!-- Search & Filters Toolbar -->
            <div class="p-4 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
                <!-- Search Input with Icon -->
                <div class="relative flex-1 max-w-md">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8" stroke-width="2"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    <input
                        v-model="search"
                        @keydown.enter="applyFilters"
                        type="text"
                        placeholder="Search by title, slug, or keywords..."
                        class="w-full pl-10 pr-4 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                    />
                </div>

                <!-- Selects & Action Buttons -->
                <div class="flex flex-wrap items-center gap-2.5">
                    <select
                        v-model="category"
                        @change="applyFilters"
                        class="px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-slate-200 focus:outline-none focus:border-emerald-500"
                    >
                        <option value="">All Categories</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.slug">{{ cat.name }}</option>
                    </select>

                    <select
                        v-model="sort"
                        @change="applyFilters"
                        class="px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-slate-200 focus:outline-none focus:border-emerald-500"
                    >
                        <option value="newest">Sort: Newest</option>
                        <option value="oldest">Sort: Oldest</option>
                        <option value="title_asc">Sort: Title A-Z</option>
                        <option value="title_desc">Sort: Title Z-A</option>
                        <option value="plays_desc">Sort: Most Played</option>
                    </select>

                    <button
                        @click="applyFilters"
                        class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-bold text-white transition-colors"
                    >
                        Filter
                    </button>

                    <button
                        v-if="hasActiveFilters()"
                        @click="resetFilters"
                        class="px-3 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 border border-slate-800 text-xs font-semibold text-slate-400 hover:text-white transition-colors"
                        title="Reset all filters"
                    >
                        Clear
                    </button>
                </div>
            </div>

            <!-- Desktop Games Table (hidden on mobile) -->
            <div class="hidden md:block rounded-2xl bg-[#0a0f1d] border border-slate-800/80 overflow-hidden shadow-md">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-950/60 border-b border-slate-800/80 text-slate-400 font-bold uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="p-4">Game</th>
                            <th class="p-4">Category</th>
                            <th class="p-4">Type</th>
                            <th class="p-4">Status</th>
                            <th class="p-4">Plays</th>
                            <th class="p-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        <tr v-if="games.data.length === 0">
                            <td colspan="6" class="p-12 text-center text-slate-500 text-xs">
                                <div class="max-w-xs mx-auto">
                                    <svg class="w-10 h-10 mx-auto mb-2 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <circle cx="11" cy="11" r="8" stroke-width="2"/>
                                        <line x1="21" y1="21" x2="16.65" y2="16.65" stroke-width="2" stroke-linecap="round"/>
                                    </svg>
                                    <p class="font-semibold text-slate-300">No games found</p>
                                    <p class="text-[11px] text-slate-400 mt-1">Try adjusting your filters or search keywords.</p>
                                    <button
                                        v-if="hasActiveFilters()"
                                        @click="resetFilters"
                                        class="mt-3 px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs text-white"
                                    >
                                        Clear Filters
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-for="game in games.data" :key="game.id" class="hover:bg-slate-900/40 transition-colors">
                            <!-- Game Info with Thumbnail -->
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <img
                                        :src="game.thumbnail || '/images/thumbnails/default-game.svg'"
                                        :alt="game.title"
                                        class="w-12 h-9 rounded-lg object-cover bg-slate-800 shrink-0 border border-slate-800"
                                    />
                                    <div class="min-w-0 max-w-xs sm:max-w-sm">
                                        <div class="font-bold text-white text-xs truncate flex items-center gap-1.5">
                                            <span>{{ game.title }}</span>
                                            <span v-if="game.is_featured" class="px-1.5 py-0.2 text-[9px] font-bold rounded bg-purple-500/10 text-purple-400 border border-purple-500/30">
                                                Featured
                                            </span>
                                        </div>
                                        <div class="text-[11px] text-slate-400 font-mono truncate">
                                            /games/{{ game.slug }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Category -->
                            <td class="p-4 text-slate-300">
                                <span v-if="game.category" class="px-2.5 py-1 rounded-lg bg-slate-800/80 border border-slate-700/60 text-[11px] font-medium">
                                    {{ game.category.name }}
                                </span>
                                <span v-else class="text-slate-500 italic">None</span>
                            </td>

                            <!-- Game Type -->
                            <td class="p-4 font-mono text-[11px] text-slate-400">
                                {{ game.game_type }}
                            </td>

                            <!-- Status Badge -->
                            <td class="p-4">
                                <StatusBadge :status="game.status" />
                            </td>

                            <!-- Plays Count -->
                            <td class="p-4 font-bold text-slate-200">
                                {{ game.plays_count.toLocaleString() }}
                            </td>

                            <!-- Action Buttons -->
                            <td class="p-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <Link
                                        :href="route('admin.games.show', game.id)"
                                        title="Inspect & Review"
                                        class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold transition-colors"
                                    >
                                        Inspect
                                    </Link>
                                    <Link
                                        :href="route('admin.games.edit', game.id)"
                                        title="Edit Metadata"
                                        class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold transition-colors"
                                    >
                                        Edit
                                    </Link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination Footer -->
                <div v-if="games.links && games.links.length > 3" class="p-4 border-t border-slate-800/80 bg-slate-950/40 flex items-center justify-between">
                    <span class="text-xs text-slate-400">
                        Showing {{ games.from || 0 }} to {{ games.to || 0 }} of {{ games.total }} games
                    </span>
                    <Pagination :links="games.links" />
                </div>
            </div>

            <!-- Mobile Games Cards List (displayed on mobile instead of broken table) -->
            <div class="md:hidden space-y-3">
                <div v-if="games.data.length === 0" class="p-8 rounded-2xl bg-[#0a0f1d] border border-slate-800 text-center text-slate-500 text-xs">
                    No games found matching criteria.
                </div>

                <div
                    v-for="game in games.data"
                    :key="game.id"
                    class="p-4 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 space-y-3"
                >
                    <div class="flex items-center gap-3">
                        <img
                            :src="game.thumbnail || '/images/thumbnails/default-game.svg'"
                            :alt="game.title"
                            class="w-14 h-11 rounded-lg object-cover bg-slate-800 shrink-0 border border-slate-800"
                        />
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <h4 class="font-bold text-white text-xs truncate">{{ game.title }}</h4>
                                <StatusBadge :status="game.status" size="sm" />
                            </div>
                            <p class="text-[11px] text-slate-400 font-mono truncate">/games/{{ game.slug }}</p>
                            <p class="text-[11px] text-slate-400 mt-0.5">
                                {{ game.category?.name || 'Unassigned' }} • {{ game.plays_count.toLocaleString() }} plays
                            </p>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-slate-800/60 flex items-center justify-between gap-2">
                        <span class="text-[10px] font-mono text-slate-400">{{ game.game_type }}</span>
                        <div class="flex items-center gap-2">
                            <Link
                                :href="route('admin.games.show', game.id)"
                                class="px-3 py-1.5 rounded-lg bg-slate-800 text-slate-200 text-xs font-semibold"
                            >
                                Inspect
                            </Link>
                            <Link
                                :href="route('admin.games.edit', game.id)"
                                class="px-3 py-1.5 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-xs font-semibold"
                            >
                                Edit
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Mobile Pagination Footer -->
                <div v-if="games.links && games.links.length > 3" class="pt-2 flex justify-center">
                    <Pagination :links="games.links" />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
