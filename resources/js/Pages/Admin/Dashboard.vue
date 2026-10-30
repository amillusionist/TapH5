<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminPageHeader from '@/Components/Admin/AdminPageHeader.vue';
import StatCard from '@/Components/Admin/StatCard.vue';
import StatusBadge from '@/Components/Admin/StatusBadge.vue';
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
        <div class="space-y-8 max-w-7xl mx-auto">
            <!-- Header with Quick Action Buttons -->
            <AdminPageHeader
                title="Operations Dashboard"
                description="Real-time portal performance, catalog diagnostics, and review queue management."
                :breadcrumbs="[{ label: 'Admin', href: route('admin.dashboard') }, { label: 'Dashboard' }]"
            >
                <template #actions>
                    <Link
                        v-if="stats.draft_games > 0"
                        :href="route('admin.games.index', { status: 'draft' })"
                        class="px-4 py-2 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 border border-amber-500/30 text-xs font-bold uppercase tracking-wider transition-all flex items-center gap-2 shadow-sm"
                    >
                        <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                        <span>Review Drafts ({{ stats.draft_games }})</span>
                    </Link>

                    <Link
                        :href="route('admin.categories.create')"
                        class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-200 border border-slate-800 text-xs font-semibold transition-colors flex items-center gap-1.5"
                    >
                        <span>+ Category</span>
                    </Link>

                    <Link
                        :href="route('admin.tags.create')"
                        class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-200 border border-slate-800 text-xs font-semibold transition-colors flex items-center gap-1.5"
                    >
                        <span>+ Tag</span>
                    </Link>
                </template>
            </AdminPageHeader>

            <!-- Pending Review Priority Banner (if drafts exist) -->
            <div
                v-if="pendingReviewGames.length > 0"
                class="p-5 sm:p-6 rounded-2xl bg-gradient-to-r from-amber-950/40 via-amber-900/20 to-slate-900/60 border border-amber-500/30 shadow-lg shadow-amber-950/20 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4"
            >
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm sm:text-base font-bold text-white">
                            {{ stats.draft_games }} {{ stats.draft_games === 1 ? 'game requires' : 'games require' }} administrative review
                        </h2>
                        <p class="text-xs text-amber-200/70 mt-0.5">
                            Auto-discovered games have been scanned and safely staged. Inspect entry points and approve for public portal listing.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    <Link
                        v-if="pendingReviewGames[0]"
                        :href="route('admin.games.show', pendingReviewGames[0].id)"
                        class="px-4 py-2 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 text-xs font-black uppercase tracking-wider transition-all shadow-md shadow-amber-950/40"
                    >
                        Review First Game →
                    </Link>
                    <Link
                        :href="route('admin.games.index', { status: 'draft' })"
                        class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-300 text-xs font-semibold transition-colors"
                    >
                        View All ({{ stats.draft_games }})
                    </Link>
                </div>
            </div>

            <!-- Operational Metrics Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <StatCard
                    label="Total Games"
                    :value="stats.total_games.toLocaleString()"
                    icon="games"
                    variant="emerald"
                >
                    <template #helper>
                        <div class="flex items-center gap-1.5 text-[11px] text-slate-400">
                            <span class="text-emerald-400 font-semibold">{{ stats.published_games }} live</span>
                            <span>•</span>
                            <span class="text-amber-400 font-semibold">{{ stats.draft_games }} drafts</span>
                        </div>
                    </template>
                </StatCard>

                <StatCard
                    label="Published Active"
                    :value="stats.published_games.toLocaleString()"
                    icon="published"
                    variant="teal"
                    helper="Available on public portal"
                />

                <StatCard
                    label="Total Game Plays"
                    :value="stats.total_plays.toLocaleString()"
                    icon="plays"
                    variant="cyan"
                    helper="Recorded gameplay sessions"
                />

                <StatCard
                    label="Taxonomies"
                    :value="stats.total_categories"
                    badge="Categories"
                    icon="categories"
                    variant="indigo"
                >
                    <template #helper>
                        <span class="text-slate-400 text-[11px]">
                            {{ stats.total_tags }} metadata tags configured
                        </span>
                    </template>
                </StatCard>
            </div>

            <!-- Two-Column Activity & Inventory Feeds -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Review Queue (Drafts Awaiting Approval) -->
                <div class="rounded-2xl bg-[#0a0f1d] border border-slate-800/80 overflow-hidden shadow-md flex flex-col">
                    <div class="p-5 border-b border-slate-800/80 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                                <span>Awaiting Review</span>
                                <span
                                    v-if="pendingReviewGames.length > 0"
                                    class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-500/10 text-amber-400 border border-amber-500/30"
                                >
                                    {{ stats.draft_games }}
                                </span>
                            </h3>
                            <p class="text-xs text-slate-400 mt-0.5">Games waiting for approval and metadata verification.</p>
                        </div>
                        <Link
                            :href="route('admin.games.index', { status: 'draft' })"
                            class="text-xs font-semibold text-emerald-400 hover:text-emerald-300 transition-colors"
                        >
                            View Queue →
                        </Link>
                    </div>

                    <div class="flex-1 p-4">
                        <div v-if="pendingReviewGames.length === 0" class="py-12 text-center text-slate-500 text-xs">
                            <div class="w-10 h-10 mx-auto mb-3 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <p class="font-semibold text-slate-300">All games are up to date!</p>
                            <p class="text-slate-400 mt-1">No drafts awaiting review in the queue.</p>
                        </div>

                        <div v-else class="space-y-2.5">
                            <div
                                v-for="game in pendingReviewGames"
                                :key="game.id"
                                class="p-3 rounded-xl bg-slate-900/60 hover:bg-slate-900 border border-slate-800/80 transition-colors flex items-center justify-between gap-3"
                            >
                                <div class="flex items-center gap-3 min-w-0">
                                    <img
                                        :src="game.thumbnail || '/images/thumbnails/default-game.svg'"
                                        :alt="game.title"
                                        class="w-11 h-9 rounded-lg object-cover bg-slate-800 shrink-0 border border-slate-700/60"
                                    />
                                    <div class="min-w-0">
                                        <h4 class="text-xs font-bold text-white truncate">{{ game.title }}</h4>
                                        <p class="text-[11px] text-slate-400 truncate">
                                            {{ game.category?.name || 'Unassigned' }} • <span class="font-mono text-emerald-400">{{ game.entry_file }}</span>
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
                </div>

                <!-- Recently Cataloged / Updated Games -->
                <div class="rounded-2xl bg-[#0a0f1d] border border-slate-800/80 overflow-hidden shadow-md flex flex-col">
                    <div class="p-5 border-b border-slate-800/80 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-white">Recently Cataloged</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Most recent game additions across the portal.</p>
                        </div>
                        <Link
                            :href="route('admin.games.index')"
                            class="text-xs font-semibold text-emerald-400 hover:text-emerald-300 transition-colors"
                        >
                            View All →
                        </Link>
                    </div>

                    <div class="flex-1 p-4 space-y-2.5">
                        <div
                            v-for="game in recentGames"
                            :key="game.id"
                            class="p-3 rounded-xl bg-slate-900/60 hover:bg-slate-900 border border-slate-800/80 transition-colors flex items-center justify-between gap-3"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <img
                                    :src="game.thumbnail || '/images/thumbnails/default-game.svg'"
                                    :alt="game.title"
                                    class="w-11 h-9 rounded-lg object-cover bg-slate-800 shrink-0 border border-slate-700/60"
                                />
                                <div class="min-w-0">
                                    <h4 class="text-xs font-bold text-white truncate">{{ game.title }}</h4>
                                    <p class="text-[11px] text-slate-400 truncate">
                                        {{ game.category?.name || 'Unassigned' }} • {{ game.plays_count.toLocaleString() }} plays
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <StatusBadge :status="game.status" size="sm" />
                                <Link
                                    :href="route('admin.games.show', game.id)"
                                    class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors"
                                    title="Inspect Game"
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

            <!-- Platform Architecture & Health Card -->
            <div class="p-5 rounded-2xl bg-slate-950/60 border border-slate-800/80">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-400 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-white uppercase tracking-wider">System Architecture Status</h4>
                            <p class="text-[11px] text-slate-400 mt-0.5">
                                Single-Domain Static Routing • Hardened Iframe Sandboxing (<code class="text-emerald-400 font-mono">allow-scripts allow-forms allow-pointer-lock</code>)
                            </p>
                        </div>
                    </div>
                    <div class="text-[11px] font-mono text-slate-400 flex items-center gap-3">
                        <span>Storage: <span class="text-slate-300">public/games</span></span>
                        <span>•</span>
                        <span>Archived: <span class="text-slate-300">{{ stats.archived_games }}</span></span>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
