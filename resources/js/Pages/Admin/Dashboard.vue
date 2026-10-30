<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type { Game } from '@/types';

interface Stats {
    total_games: number;
    published_games: number;
    draft_games: number;
    archived_games: number;
    total_categories: number;
    total_tags: number;
    total_plays: number;
}

defineProps<{
    stats: Stats;
    recentGames: Game[];
    recentUpdatedGames: Game[];
    pendingReviewGames: Game[];
}>();
</script>

<template>
    <AdminLayout title="Dashboard Overview">
        <div class="space-y-8">
            <!-- Header Greeting & Quick Actions -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2">
                <div>
                    <h2 class="text-2xl font-extrabold text-white tracking-tight">Portal Operations</h2>
                    <p class="text-sm text-slate-400 mt-1">Real-time database statistics and platform management.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <Link
                        :href="route('admin.games.index', { status: 'draft' })"
                        class="px-4 py-2 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 border border-amber-500/30 text-xs font-bold uppercase tracking-wider transition-all flex items-center gap-1.5"
                    >
                        <span class="w-2 h-2 rounded-full bg-amber-400 animate-ping"></span>
                        <span>Review Drafts ({{ stats.draft_games }})</span>
                    </Link>
                    <Link
                        :href="route('admin.categories.create')"
                        class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold uppercase tracking-wider transition-colors"
                    >
                        + Add Category
                    </Link>
                    <Link
                        :href="route('admin.tags.create')"
                        class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold uppercase tracking-wider transition-colors"
                    >
                        + Add Tag
                    </Link>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Games -->
                <div class="p-5 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 shadow-md">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Games</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-400 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <polygon points="6 3 20 12 6 21 6 3" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl sm:text-3xl font-black text-white">{{ stats.total_games.toLocaleString() }}</div>
                    <div class="mt-2 text-xs text-slate-400 flex items-center gap-1.5">
                        <span class="text-emerald-400 font-semibold">{{ stats.published_games }} Published</span>
                        <span>•</span>
                        <span class="text-amber-400 font-semibold">{{ stats.draft_games }} Drafts</span>
                    </div>
                </div>

                <!-- Published Games -->
                <div class="p-5 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 shadow-md">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Published Active</span>
                        <div class="w-8 h-8 rounded-lg bg-teal-500/10 text-teal-400 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" stroke-width="2"/>
                                <path d="m9 12 2 2 4-4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl sm:text-3xl font-black text-white">{{ stats.published_games.toLocaleString() }}</div>
                    <div class="mt-2 text-xs text-slate-400">
                        Live on portal catalog
                    </div>
                </div>

                <!-- Total Game Plays -->
                <div class="p-5 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 shadow-md">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Plays</span>
                        <div class="w-8 h-8 rounded-lg bg-cyan-500/10 text-cyan-400 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" stroke-width="2"/>
                                <polygon points="10 8 16 12 10 16 10 8" fill="currentColor"/>
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl sm:text-3xl font-black text-white">{{ stats.total_plays.toLocaleString() }}</div>
                    <div class="mt-2 text-xs text-slate-400">
                        Total recorded play sessions
                    </div>
                </div>

                <!-- Taxonomies -->
                <div class="p-5 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 shadow-md">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Taxonomies</span>
                        <div class="w-8 h-8 rounded-lg bg-indigo-500/10 text-indigo-400 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl sm:text-3xl font-black text-white">{{ stats.total_categories }} <span class="text-sm font-medium text-slate-400">Categories</span></div>
                    <div class="mt-2 text-xs text-slate-400">
                        {{ stats.total_tags }} Tags configured
                    </div>
                </div>
            </div>

            <!-- Two-Column Activity Feeds -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Draft Review Queue -->
                <div class="p-6 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-bold text-white flex items-center gap-2">
                                <span>Awaiting Review</span>
                                <span v-if="pendingReviewGames.length > 0" class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-500/10 text-amber-400 border border-amber-500/30">
                                    {{ stats.draft_games }}
                                </span>
                            </h3>
                            <p class="text-xs text-slate-400 mt-0.5">Scanned games waiting for approval and publishing.</p>
                        </div>
                        <Link
                            :href="route('admin.games.index', { status: 'draft' })"
                            class="text-xs font-semibold text-emerald-400 hover:text-emerald-300"
                        >
                            View All →
                        </Link>
                    </div>

                    <div v-if="pendingReviewGames.length === 0" class="py-8 text-center text-slate-400 text-xs">
                        <svg class="w-8 h-8 mx-auto mb-2 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke-width="2"/>
                            <path d="m9 12 2 2 4-4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        All scanned games are reviewed and published!
                    </div>

                    <div v-else class="space-y-3">
                        <div
                            v-for="game in pendingReviewGames"
                            :key="game.id"
                            class="p-3 rounded-xl bg-slate-900/60 border border-slate-800 flex items-center justify-between gap-3"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <img
                                    :src="game.thumbnail || '/images/thumbnails/default-game.svg'"
                                    :alt="game.title"
                                    class="w-10 h-10 rounded-lg object-cover bg-slate-800 shrink-0"
                                />
                                <div class="min-w-0">
                                    <h4 class="text-xs font-bold text-white truncate">{{ game.title }}</h4>
                                    <p class="text-[11px] text-slate-400 truncate">
                                        {{ game.category?.name || 'Unassigned' }} • {{ game.entry_file }}
                                    </p>
                                </div>
                            </div>
                            <Link
                                :href="route('admin.games.show', game.id)"
                                class="px-3 py-1.5 rounded-lg bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 text-xs font-bold shrink-0 transition-colors"
                            >
                                Review
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Recently Added Games -->
                <div class="p-6 rounded-2xl bg-[#0a0f1d] border border-slate-800/80 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-bold text-white">Recently Cataloged</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Most recently created game entries.</p>
                        </div>
                        <Link
                            :href="route('admin.games.index')"
                            class="text-xs font-semibold text-emerald-400 hover:text-emerald-300"
                        >
                            View All →
                        </Link>
                    </div>

                    <div class="space-y-3">
                        <div
                            v-for="game in recentGames"
                            :key="game.id"
                            class="p-3 rounded-xl bg-slate-900/60 border border-slate-800 flex items-center justify-between gap-3"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <img
                                    :src="game.thumbnail || '/images/thumbnails/default-game.svg'"
                                    :alt="game.title"
                                    class="w-10 h-10 rounded-lg object-cover bg-slate-800 shrink-0"
                                />
                                <div class="min-w-0">
                                    <h4 class="text-xs font-bold text-white truncate">{{ game.title }}</h4>
                                    <p class="text-[11px] text-slate-400 truncate">
                                        {{ game.category?.name || 'Unassigned' }} • {{ game.plays_count }} plays
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span
                                    class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide border"
                                    :class="{
                                        'bg-emerald-500/10 text-emerald-400 border-emerald-500/30': game.status === 'published',
                                        'bg-amber-500/10 text-amber-400 border-amber-500/30': game.status === 'draft',
                                        'bg-slate-700/20 text-slate-400 border-slate-700/50': game.status === 'archived',
                                    }"
                                >
                                    {{ game.status }}
                                </span>
                                <Link
                                    :href="route('admin.games.show', game.id)"
                                    class="p-1 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800"
                                    title="Inspect"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path d="M5 12h14M12 5l7 7-7 7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

