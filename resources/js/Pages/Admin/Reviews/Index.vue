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
    filters: {
        status: string;
        search: string;
        category: string;
    };
    counts: {
        pending: number;
        changes_requested: number;
        approved: number;
        rejected: number;
        all: number;
    };
    categories: Category[];
    canPublish: boolean;
}>();

const currentStatus = ref(props.filters.status || 'pending');
const search = ref(props.filters.search || '');
const category = ref(props.filters.category || '');

const applyFilters = () => {
    router.get(
        route('admin.game_reviews.index'),
        {
            status: currentStatus.value,
            search: search.value || undefined,
            category: category.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

const switchTab = (statusTab: string) => {
    currentStatus.value = statusTab;
    applyFilters();
};

const resetFilters = () => {
    search.value = '';
    category.value = '';
    applyFilters();
};

const formatDate = (dateStr?: string | null) => {
    if (!dateStr) return '—';
    try {
        const d = new Date(dateStr);
        return d.toLocaleDateString(undefined, {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return dateStr;
    }
};
</script>

<template>
    <AdminLayout title="Game Review Queue">
        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Header -->
            <AdminPageHeader
                title="Game Review & Operations Queue"
                description="Manage content review lifecycle: verify staged builds, inspect sandbox behavior, request revisions, and approve for publishing."
                :breadcrumbs="[
                    { label: 'Admin', href: route('admin.dashboard') },
                    { label: 'Game Reviews' },
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.games.add')"
                        class="px-4 py-2 bg-gradient-to-r from-indigo-500 to-indigo-600 hover:from-indigo-400 hover:to-indigo-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-indigo-500/20 transition-all flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Add Staged Game</span>
                    </Link>
                </template>
            </AdminPageHeader>

            <!-- Filter Tabs -->
            <div class="p-2 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 flex flex-wrap gap-1.5">
                <!-- Pending -->
                <button
                    type="button"
                    @click="switchTab('pending')"
                    :class="[
                        'px-4 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2',
                        currentStatus === 'pending'
                            ? 'bg-amber-500/15 border border-amber-500/40 text-amber-300 shadow-md shadow-amber-500/10'
                            : 'bg-slate-900/60 border border-slate-800 text-slate-400 hover:text-slate-200'
                    ]"
                >
                    <span>Pending Review</span>
                    <span
                        class="px-2 py-0.5 rounded-full text-[10px] font-black"
                        :class="currentStatus === 'pending' ? 'bg-amber-500 text-slate-950' : 'bg-slate-800 text-slate-400'"
                    >
                        {{ counts.pending }}
                    </span>
                </button>

                <!-- Changes Requested -->
                <button
                    type="button"
                    @click="switchTab('changes_requested')"
                    :class="[
                        'px-4 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2',
                        currentStatus === 'changes_requested'
                            ? 'bg-orange-500/15 border border-orange-500/40 text-orange-300 shadow-md shadow-orange-500/10'
                            : 'bg-slate-900/60 border border-slate-800 text-slate-400 hover:text-slate-200'
                    ]"
                >
                    <span>Changes Requested</span>
                    <span
                        class="px-2 py-0.5 rounded-full text-[10px] font-black"
                        :class="currentStatus === 'changes_requested' ? 'bg-orange-500 text-slate-950' : 'bg-slate-800 text-slate-400'"
                    >
                        {{ counts.changes_requested }}
                    </span>
                </button>

                <!-- Approved -->
                <button
                    type="button"
                    @click="switchTab('approved')"
                    :class="[
                        'px-4 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2',
                        currentStatus === 'approved'
                            ? 'bg-emerald-500/15 border border-emerald-500/40 text-emerald-300 shadow-md shadow-emerald-500/10'
                            : 'bg-slate-900/60 border border-slate-800 text-slate-400 hover:text-slate-200'
                    ]"
                >
                    <span>Approved</span>
                    <span
                        class="px-2 py-0.5 rounded-full text-[10px] font-black"
                        :class="currentStatus === 'approved' ? 'bg-emerald-500 text-slate-950' : 'bg-slate-800 text-slate-400'"
                    >
                        {{ counts.approved }}
                    </span>
                </button>

                <!-- Rejected -->
                <button
                    type="button"
                    @click="switchTab('rejected')"
                    :class="[
                        'px-4 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2',
                        currentStatus === 'rejected'
                            ? 'bg-rose-500/15 border border-rose-500/40 text-rose-300 shadow-md shadow-rose-500/10'
                            : 'bg-slate-900/60 border border-slate-800 text-slate-400 hover:text-slate-200'
                    ]"
                >
                    <span>Rejected</span>
                    <span
                        class="px-2 py-0.5 rounded-full text-[10px] font-black"
                        :class="currentStatus === 'rejected' ? 'bg-rose-500 text-slate-950' : 'bg-slate-800 text-slate-400'"
                    >
                        {{ counts.rejected }}
                    </span>
                </button>

                <!-- All Drafts -->
                <button
                    type="button"
                    @click="switchTab('all')"
                    :class="[
                        'px-4 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2',
                        currentStatus === 'all'
                            ? 'bg-indigo-500/15 border border-indigo-500/40 text-indigo-300 shadow-md shadow-indigo-500/10'
                            : 'bg-slate-900/60 border border-slate-800 text-slate-400 hover:text-slate-200'
                    ]"
                >
                    <span>All Drafts</span>
                    <span
                        class="px-2 py-0.5 rounded-full text-[10px] font-black"
                        :class="currentStatus === 'all' ? 'bg-indigo-500 text-slate-950' : 'bg-slate-800 text-slate-400'"
                    >
                        {{ counts.all }}
                    </span>
                </button>
            </div>

            <!-- Search & Filters Bar -->
            <div class="p-4 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="flex flex-1 items-center gap-2 w-full">
                    <!-- Search input -->
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-slate-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <circle cx="11" cy="11" r="8" stroke-width="2" />
                                <line x1="21" y1="21" x2="16.65" y2="16.65" stroke-width="2" />
                            </svg>
                        </span>
                        <input
                            v-model="search"
                            @keyup.enter="applyFilters"
                            type="text"
                            placeholder="Search by title or slug..."
                            class="w-full bg-slate-900 border border-slate-800 rounded-xl pl-9 pr-3 py-2 text-xs text-slate-200 placeholder-slate-500 focus:border-indigo-500 focus:outline-none"
                        />
                    </div>

                    <!-- Category select -->
                    <select
                        v-model="category"
                        @change="applyFilters"
                        class="bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-200 focus:border-indigo-500 focus:outline-none"
                    >
                        <option value="">All Categories</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.slug">
                            {{ cat.name }}
                        </option>
                    </select>

                    <button
                        type="button"
                        @click="applyFilters"
                        class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold rounded-xl transition-colors"
                    >
                        Filter
                    </button>

                    <button
                        v-if="search || category"
                        type="button"
                        @click="resetFilters"
                        class="px-3 py-2 bg-slate-900 hover:bg-slate-800 text-slate-400 text-xs font-semibold rounded-xl border border-slate-800 transition-colors"
                    >
                        Reset
                    </button>
                </div>
            </div>

            <!-- Queue Games Table -->
            <div class="rounded-2xl bg-[#0a0f1d] border border-slate-800/80 overflow-hidden shadow-md">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-950/60 border-b border-slate-800/80 text-slate-400 font-bold uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="p-4">Game</th>
                            <th class="p-4">Category</th>
                            <th class="p-4">Review State</th>
                            <th class="p-4">Submitted By</th>
                            <th class="p-4">Last Reviewer</th>
                            <th class="p-4">Notes / Feedback</th>
                            <th class="p-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        <tr v-if="games.data.length === 0">
                            <td colspan="7" class="p-12 text-center text-slate-400 text-xs space-y-2">
                                <svg class="w-12 h-12 mx-auto text-slate-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="font-bold text-slate-300 text-sm">No games in this review queue.</p>
                                <p class="text-slate-500">
                                    {{ currentStatus === 'pending'
                                        ? 'All submitted games have been reviewed or none are pending.'
                                        : 'No draft games found matching this status.' }}
                                </p>
                            </td>
                        </tr>

                        <tr
                            v-for="game in games.data"
                            :key="game.id"
                            class="hover:bg-slate-900/40 transition-colors"
                        >
                            <!-- Game Info -->
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <img
                                        :src="game.thumbnail || '/images/thumbnails/default-game.svg'"
                                        :alt="game.title"
                                        class="w-12 h-9 rounded-lg object-cover bg-slate-800 shrink-0 border border-slate-800"
                                    />
                                    <div class="min-w-0 max-w-xs">
                                        <div class="font-bold text-white text-xs truncate">
                                            {{ game.title }}
                                        </div>
                                        <div class="text-[11px] text-slate-400 font-mono truncate">
                                            /games/{{ game.slug }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Category -->
                            <td class="p-4 text-slate-300">
                                <span v-if="game.category" class="px-2 py-0.5 rounded-lg bg-slate-800/80 border border-slate-700/60 text-[11px] font-medium">
                                    {{ game.category.name }}
                                </span>
                                <span v-else class="text-slate-500 italic">None</span>
                            </td>

                            <!-- Review State -->
                            <td class="p-4">
                                <StatusBadge :status="game.review_status || 'not_submitted'" />
                            </td>

                            <!-- Submitter -->
                            <td class="p-4 text-slate-300">
                                <div v-if="game.submitted_by_user" class="min-w-0">
                                    <div class="font-semibold text-white truncate text-xs">{{ game.submitted_by_user.name }}</div>
                                    <div class="text-[10px] text-slate-400 truncate">{{ formatDate(game.submitted_at) }}</div>
                                </div>
                                <span v-else class="text-slate-500 italic text-[11px]">Unrecorded</span>
                            </td>

                            <!-- Reviewer -->
                            <td class="p-4 text-slate-300">
                                <div v-if="game.reviewed_by_user" class="min-w-0">
                                    <div class="font-semibold text-white truncate text-xs">{{ game.reviewed_by_user.name }}</div>
                                    <div class="text-[10px] text-slate-400 truncate">{{ formatDate(game.reviewed_at) }}</div>
                                </div>
                                <span v-else class="text-slate-500 italic text-[11px]">Pending assignment</span>
                            </td>

                            <!-- Notes / Feedback excerpt -->
                            <td class="p-4 max-w-xs">
                                <p v-if="game.review_notes" class="text-[11px] text-slate-300 truncate" :title="game.review_notes">
                                    {{ game.review_notes }}
                                </p>
                                <span v-else class="text-slate-500 text-[11px] italic">No notes</span>
                            </td>

                            <!-- Action -->
                            <td class="p-4 text-right">
                                <Link
                                    :href="route('admin.game_reviews.show', game.id)"
                                    class="px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs transition-all shadow-md shadow-indigo-600/20 inline-flex items-center gap-1.5"
                                >
                                    <span>Inspect & Review</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                    </svg>
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination footer -->
                <div v-if="games.links && games.links.length > 3" class="p-4 border-t border-slate-800/80 bg-slate-950/40 flex items-center justify-between">
                    <span class="text-xs text-slate-400">
                        Showing {{ games.from || 0 }} to {{ games.to || 0 }} of {{ games.total }} reviews
                    </span>
                    <Pagination :links="games.links" />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

