<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminPageHeader from '@/Components/Admin/AdminPageHeader.vue';
import StatusBadge from '@/Components/Admin/StatusBadge.vue';
import Pagination from '@/Components/Pagination.vue';
import type { Category, Game, PaginatedData, Tag } from '@/types';

const props = defineProps<{
    games: PaginatedData<Game & { live_players?: number }>;
    categories: Category[];
    tags: Tag[];
    filters: {
        search?: string;
        status?: string;
        category?: string;
        game_type?: string;
        flag?: string;
        sort?: string;
    };
    statusCounts: {
        all: number;
        published: number;
        draft: number;
        archived: number;
        featured: number;
        trending: number;
        popular: number;
    };
}>();

const search = ref(props.filters.search || '');
const category = ref(props.filters.category || '');
const status = ref(props.filters.status || '');
const flag = ref(props.filters.flag || '');
const sort = ref(props.filters.sort || 'newest');

const applyFilters = () => {
    router.get(
        route('admin.games.index'),
        {
            search: search.value || undefined,
            status: status.value || undefined,
            category: category.value || undefined,
            flag: flag.value || undefined,
            sort: sort.value || undefined,
        },
        { preserveState: true, replace: true }
    );
};

const filterByStatus = (statusValue: string) => {
    status.value = statusValue;
    flag.value = '';
    applyFilters();
};

const filterByFlag = (flagValue: string) => {
    flag.value = flagValue;
    status.value = '';
    applyFilters();
};

const resetFilters = () => {
    search.value = '';
    category.value = '';
    status.value = '';
    flag.value = '';
    sort.value = 'newest';
    applyFilters();
};

const hasActiveFilters = () => {
    return !!(search.value || category.value || status.value || flag.value || (sort.value && sort.value !== 'newest'));
};

// Bulk Actions State
const selectedGameIds = ref<number[]>([]);
const isAllSelected = computed(() => {
    return props.games.data.length > 0 && selectedGameIds.value.length === props.games.data.length;
});

const toggleSelectAll = () => {
    if (isAllSelected.value) {
        selectedGameIds.value = [];
    } else {
        selectedGameIds.value = props.games.data.map(g => g.id);
    }
};

const toggleSelectGame = (id: number) => {
    const idx = selectedGameIds.value.indexOf(id);
    if (idx > -1) {
        selectedGameIds.value.splice(idx, 1);
    } else {
        selectedGameIds.value.push(id);
    }
};

const bulkForm = useForm<{
    action: string;
    game_ids: number[];
    category_id: number | null;
    tag_ids: number[];
}>({
    action: 'publish',
    game_ids: [],
    category_id: null,
    tag_ids: [],
});

const executeBulkAction = () => {
    if (selectedGameIds.value.length === 0) return;

    if (bulkForm.action === 'delete') {
        if (!confirm(`Warning: You are about to permanently delete ${selectedGameIds.value.length} draft/archived game(s). Published games will not be deleted without archiving first. Continue?`)) {
            return;
        }
    } else {
        if (!confirm(`Apply action '${bulkForm.action}' to ${selectedGameIds.value.length} selected game(s)?`)) {
            return;
        }
    }

    bulkForm.game_ids = [...selectedGameIds.value];
    bulkForm.post(route('admin.games.bulk'), {
        preserveScroll: true,
        onSuccess: () => {
            selectedGameIds.value = [];
            bulkForm.reset();
        },
    });
};

// Batch Catalog Integrity Check
const isCheckingIntegrity = ref(false);
const integrityReport = ref<any | null>(null);

const runBatchValidate = async () => {
    isCheckingIntegrity.value = true;
    try {
        const res = await fetch(route('admin.games.batch_validate'));
        integrityReport.value = await res.json();
    } catch (e) {
        alert('Failed to execute catalog integrity check.');
    } finally {
        isCheckingIntegrity.value = false;
    }
};
</script>

<template>
    <AdminLayout title="Games Management">
        <div class="space-y-6 max-w-7xl mx-auto">
            <!-- Header -->
            <AdminPageHeader
                title="Games Catalog Manager"
                description="Review, inspect, edit, publish, bulk-manage, and validate all HTML5 games on the platform."
                :breadcrumbs="[{ label: 'Admin', href: route('admin.dashboard') }, { label: 'Games' }]"
            >
                <template #actions>
                    <button
                        type="button"
                        @click="runBatchValidate"
                        :disabled="isCheckingIntegrity"
                        class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs border border-slate-700 flex items-center gap-1.5 transition-colors"
                    >
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ isCheckingIntegrity ? 'Checking Files...' : 'Catalog Integrity Audit' }}</span>
                    </button>
                </template>
            </AdminPageHeader>

            <!-- Status & Flag Filters -->
            <div class="flex flex-wrap items-center gap-2">
                <button
                    @click="filterByStatus('')"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2"
                    :class="!status && !flag
                        ? 'bg-emerald-500 text-slate-950 shadow-md shadow-emerald-500/20'
                        : 'bg-slate-900 text-slate-400 hover:text-white hover:bg-slate-800 border border-slate-800'"
                >
                    <span>All Games</span>
                    <span class="px-1.5 py-0.5 rounded-md text-[10px] bg-slate-950/20 font-black">
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
                    <span class="px-1.5 py-0.5 rounded-md text-[10px] bg-amber-500/10 text-amber-400 border border-amber-500/20">
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
                    <span class="px-1.5 py-0.5 rounded-md text-[10px] bg-slate-800 text-slate-400">
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
                    <span class="px-1.5 py-0.5 rounded-md text-[10px] bg-slate-800 text-slate-400">
                        {{ statusCounts.archived }}
                    </span>
                </button>

                <div class="h-4 w-px bg-slate-800 hidden sm:block mx-1"></div>

                <!-- Flag Quick Filters -->
                <button
                    @click="filterByFlag('featured')"
                    :class="flag === 'featured' ? 'bg-purple-500 text-slate-950 font-bold' : 'bg-slate-900 text-slate-400 border border-slate-800'"
                    class="px-3 py-1.5 rounded-xl text-xs transition-colors flex items-center gap-1.5"
                >
                    <span>★ Featured</span>
                    <span class="text-[10px] font-mono">({{ statusCounts.featured }})</span>
                </button>

                <button
                    @click="filterByFlag('trending')"
                    :class="flag === 'trending' ? 'bg-cyan-500 text-slate-950 font-bold' : 'bg-slate-900 text-slate-400 border border-slate-800'"
                    class="px-3 py-1.5 rounded-xl text-xs transition-colors flex items-center gap-1.5"
                >
                    <span>🔥 Trending</span>
                    <span class="text-[10px] font-mono">({{ statusCounts.trending }})</span>
                </button>

                <button
                    @click="filterByFlag('popular')"
                    :class="flag === 'popular' ? 'bg-amber-500 text-slate-950 font-bold' : 'bg-slate-900 text-slate-400 border border-slate-800'"
                    class="px-3 py-1.5 rounded-xl text-xs transition-colors flex items-center gap-1.5"
                >
                    <span>♥ Popular</span>
                    <span class="text-[10px] font-mono">({{ statusCounts.popular }})</span>
                </button>
            </div>

            <!-- Bulk Actions Floating Bar -->
            <div
                v-if="selectedGameIds.length > 0"
                class="p-4 rounded-2xl bg-emerald-950/40 border border-emerald-500/40 flex flex-wrap items-center justify-between gap-3 shadow-2xl animate-fade-in"
            >
                <div class="flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full bg-emerald-500 text-slate-950 font-black text-xs flex items-center justify-center">
                        {{ selectedGameIds.length }}
                    </span>
                    <span class="text-xs font-bold text-white">game(s) selected for bulk management</span>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <select
                        v-model="bulkForm.action"
                        class="px-3 py-1.5 rounded-xl bg-slate-950 border border-slate-700 text-white text-xs font-semibold focus:ring-1 focus:ring-emerald-400"
                    >
                        <option value="publish">Publish Eligible Drafts</option>
                        <option value="archive">Archive Selected</option>
                        <option value="change_category">Change Category</option>
                        <option value="set_featured">Set Featured (★)</option>
                        <option value="unset_featured">Unset Featured</option>
                        <option value="set_trending">Set Trending (🔥)</option>
                        <option value="unset_trending">Unset Trending</option>
                        <option value="set_popular">Set Popular (♥)</option>
                        <option value="unset_popular">Unset Popular</option>
                        <option value="delete">Delete (Draft/Archived Only)</option>
                    </select>

                    <!-- Category picker if change_category -->
                    <select
                        v-if="bulkForm.action === 'change_category'"
                        v-model="bulkForm.category_id"
                        class="px-3 py-1.5 rounded-xl bg-slate-950 border border-slate-700 text-white text-xs"
                    >
                        <option :value="null">-- Choose Category --</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                    </select>

                    <button
                        type="button"
                        @click="executeBulkAction"
                        :disabled="bulkForm.processing"
                        class="px-4 py-1.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs shadow transition-colors"
                    >
                        Apply Bulk Action
                    </button>

                    <button
                        type="button"
                        @click="selectedGameIds = []"
                        class="px-3 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-white text-xs font-semibold"
                    >
                        Deselect
                    </button>
                </div>
            </div>

            <!-- Search & Filters Toolbar -->
            <div class="p-4 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
                <div class="relative flex-1 max-w-md">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8" stroke-width="2"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    <input
                        v-model="search"
                        @keydown.enter="applyFilters"
                        type="text"
                        placeholder="Search by title, slug, or description..."
                        class="w-full pl-10 pr-4 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500"
                    />
                </div>

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
                    >
                        Clear
                    </button>
                </div>
            </div>

            <!-- Desktop Games Table -->
            <div class="hidden md:block rounded-2xl bg-[#0a0f1d] border border-slate-800/80 overflow-hidden shadow-md">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-950/60 border-b border-slate-800/80 text-slate-400 font-bold uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="p-4 w-10">
                                <input
                                    type="checkbox"
                                    :checked="isAllSelected"
                                    @change="toggleSelectAll"
                                    class="rounded bg-slate-900 border-slate-800 text-emerald-500 focus:ring-0 cursor-pointer"
                                />
                            </th>
                            <th class="p-4">Game</th>
                            <th class="p-4">Category</th>
                            <th class="p-4">Type</th>
                            <th class="p-4">Status</th>
                            <th class="p-4">Plays & Live</th>
                            <th class="p-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        <tr v-if="games.data.length === 0">
                            <td colspan="7" class="p-12 text-center text-slate-500 text-xs">
                                No games found matching current filters.
                            </td>
                        </tr>

                        <tr
                            v-for="game in games.data"
                            :key="game.id"
                            :class="selectedGameIds.includes(game.id) ? 'bg-emerald-500/5' : 'hover:bg-slate-900/40'"
                            class="transition-colors"
                        >
                            <td class="p-4">
                                <input
                                    type="checkbox"
                                    :checked="selectedGameIds.includes(game.id)"
                                    @change="toggleSelectGame(game.id)"
                                    class="rounded bg-slate-900 border-slate-800 text-emerald-500 focus:ring-0 cursor-pointer"
                                />
                            </td>

                            <!-- Game Info -->
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
                                            <span v-if="game.is_trending" class="px-1.5 py-0.2 text-[9px] font-bold rounded bg-cyan-500/10 text-cyan-400 border border-cyan-500/30">
                                                Trending
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

                            <!-- Plays & Live Traffic -->
                            <td class="p-4">
                                <div class="font-bold text-slate-200">
                                    {{ game.plays_count.toLocaleString() }} plays
                                </div>
                                <div v-if="game.live_players && game.live_players > 0" class="text-[10px] text-emerald-400 font-bold flex items-center gap-1 mt-0.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                    {{ game.live_players }} active now
                                </div>
                            </td>

                            <!-- Action Buttons -->
                            <td class="p-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <Link
                                        :href="route('admin.games.show', game.id)"
                                        class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold transition-colors"
                                    >
                                        Inspect
                                    </Link>
                                    <Link
                                        :href="route('admin.games.edit', game.id)"
                                        class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold transition-colors"
                                    >
                                        Edit
                                    </Link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="games.links && games.links.length > 3" class="p-4 border-t border-slate-800/80 bg-slate-950/40 flex items-center justify-between">
                    <span class="text-xs text-slate-400">
                        Showing {{ games.from || 0 }} to {{ games.to || 0 }} of {{ games.total }} games
                    </span>
                    <Pagination :links="games.links" />
                </div>
            </div>

            <!-- Mobile Games Cards List -->
            <div class="md:hidden space-y-3">
                <div v-for="game in games.data" :key="game.id" class="p-4 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 space-y-3">
                    <div class="flex items-center gap-3">
                        <input
                            type="checkbox"
                            :checked="selectedGameIds.includes(game.id)"
                            @change="toggleSelectGame(game.id)"
                            class="rounded bg-slate-900 border-slate-800 text-emerald-500 focus:ring-0"
                        />
                        <img
                            :src="game.thumbnail || '/images/thumbnails/default-game.svg'"
                            :alt="game.title"
                            class="w-12 h-9 rounded-lg object-cover bg-slate-800 shrink-0"
                        />
                        <div class="min-w-0 flex-1">
                            <h4 class="font-bold text-white text-xs truncate">{{ game.title }}</h4>
                            <div class="flex items-center gap-2 mt-0.5">
                                <StatusBadge :status="game.status" size="sm" />
                                <span class="text-[10px] text-slate-400">{{ game.plays_count }} plays</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-slate-800/60 flex items-center justify-end gap-2">
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

                <div v-if="games.links && games.links.length > 3" class="pt-2 flex justify-center">
                    <Pagination :links="games.links" />
                </div>
            </div>

            <!-- Catalog Integrity Modal -->
            <div
                v-if="integrityReport"
                class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4"
                @click.self="integrityReport = null"
            >
                <div class="w-full max-w-md rounded-2xl bg-slate-900 border border-slate-800 shadow-2xl p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <h3 class="text-base font-bold text-white">Catalog Integrity Audit</h3>
                        <button @click="integrityReport = null" class="text-slate-400 hover:text-white">&times;</button>
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div class="p-3 rounded-xl bg-slate-950 border border-slate-800">
                            <span class="text-slate-400">Total Games:</span>
                            <div class="text-lg font-bold text-white mt-1">{{ integrityReport.total_games }}</div>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-950 border border-slate-800">
                            <span class="text-slate-400">Valid Games:</span>
                            <div class="text-lg font-bold text-emerald-400 mt-1">{{ integrityReport.valid_games }}</div>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-950 border border-slate-800">
                            <span class="text-slate-400">Missing Entry Files:</span>
                            <div class="text-lg font-bold" :class="integrityReport.missing_entry_files > 0 ? 'text-rose-400' : 'text-slate-300'">
                                {{ integrityReport.missing_entry_files }}
                            </div>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-950 border border-slate-800">
                            <span class="text-slate-400">Missing Thumbnails:</span>
                            <div class="text-lg font-bold" :class="integrityReport.missing_thumbnails > 0 ? 'text-amber-400' : 'text-slate-300'">
                                {{ integrityReport.missing_thumbnails }}
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button
                            type="button"
                            @click="integrityReport = null"
                            class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold"
                        >
                            Close Report
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
