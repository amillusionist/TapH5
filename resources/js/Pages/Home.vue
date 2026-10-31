<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import HeroBanner from '@/Components/HeroBanner.vue';
import GameCard from '@/Components/GameCard.vue';
import CategoryCard from '@/Components/CategoryCard.vue';
import AdSlot from '@/Components/Ads/AdSlot.vue';
import SeoHead from '@/Components/SeoHead.vue';
import EmptyState from '@/Components/EmptyState.vue';
import type { Category, Game, SiteMeta } from '@/types';

defineProps<{
    heroGames: Game[];
    featuredGames: Game[];
    trendingGames: Game[];
    popularGames: Game[];
    newGames: Game[];
    recentlyAdded: Game[];
    allGamesPreview: Game[];
    categories: Category[];
    meta: SiteMeta;
}>();
</script>

<template>
    <SeoHead :meta="meta" />

    <AppLayout>
        <!-- Full-Width Hero Section -->
        <template #hero>
            <HeroBanner :games="heroGames" />
        </template>

        <!-- Monetization: Top Leaderboard Placement -->
        <AdSlot placement-key="home_top" class-name="mb-10" />

        <!-- Categories Bar -->
        <section class="mb-14">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-cyan-400"></span>
                        Explore Categories
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">Browse our collection organized by gameplay genre</p>
                </div>
            </div>

            <div v-if="categories.length > 0" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
                <CategoryCard
                    v-for="cat in categories"
                    :key="cat.id"
                    :category="cat"
                />
            </div>
            <EmptyState v-else title="No Categories Available" message="Categories will appear here once configured." />
        </section>

        <!-- Featured Games Section -->
        <section v-if="featuredGames.length > 0" class="mb-14">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                        Featured Games
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">Handpicked spotlight titles ready for instant play</p>
                </div>
                <Link
                    :href="route('games.index', { sort: 'featured' })"
                    class="text-xs font-bold uppercase tracking-wider text-emerald-400 hover:text-emerald-300 flex items-center gap-1 group"
                >
                    <span>View All</span>
                    <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14m-7-7 7 7-7 7"/>
                    </svg>
                </Link>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 sm:gap-4">
                <GameCard
                    v-for="(game, idx) in featuredGames"
                    :key="game.id"
                    :game="game"
                    :priority="idx < 4"
                />
            </div>
        </section>

        <!-- Trending Games Section -->
        <section v-if="trendingGames.length > 0" class="mb-14">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                        Trending Now
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">Games capturing player attention right now</p>
                </div>
                <Link
                    :href="route('games.index')"
                    class="text-xs font-bold uppercase tracking-wider text-emerald-400 hover:text-emerald-300 flex items-center gap-1 group"
                >
                    <span>View All</span>
                    <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14m-7-7 7 7-7 7"/>
                    </svg>
                </Link>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 sm:gap-4">
                <GameCard
                    v-for="game in trendingGames"
                    :key="game.id"
                    :game="game"
                />
            </div>
        </section>

        <!-- Monetization: Between Sections Placement -->
        <AdSlot placement-key="home_between_sections" class-name="mb-14" />

        <!-- New Games Section -->
        <section v-if="newGames.length > 0" class="mb-14">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-cyan-400"></span>
                        New Releases
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">Fresh arrivals added to the platform</p>
                </div>
                <Link
                    :href="route('games.index', { sort: 'newest' })"
                    class="text-xs font-bold uppercase tracking-wider text-emerald-400 hover:text-emerald-300 flex items-center gap-1 group"
                >
                    <span>View All</span>
                    <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14m-7-7 7 7-7 7"/>
                    </svg>
                </Link>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 sm:gap-4">
                <GameCard
                    v-for="game in newGames"
                    :key="game.id"
                    :game="game"
                />
            </div>
        </section>

        <!-- Popular Games Section -->
        <section v-if="popularGames.length > 0" class="mb-14">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-purple-400"></span>
                        Popular Games
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">Community favorites across all game styles</p>
                </div>
                <Link
                    :href="route('games.index', { sort: 'popular' })"
                    class="text-xs font-bold uppercase tracking-wider text-emerald-400 hover:text-emerald-300 flex items-center gap-1 group"
                >
                    <span>View All</span>
                    <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14m-7-7 7 7-7 7"/>
                    </svg>
                </Link>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 sm:gap-4">
                <GameCard
                    v-for="game in popularGames"
                    :key="game.id"
                    :game="game"
                />
            </div>
        </section>

        <!-- Recently Added Section -->
        <section v-if="recentlyAdded.length > 0" class="mb-14">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-rose-400"></span>
                        Recently Added
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">Latest games cataloged on TapH5</p>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 sm:gap-4">
                <GameCard
                    v-for="game in recentlyAdded"
                    :key="game.id"
                    :game="game"
                />
            </div>
        </section>

        <!-- All Games Teaser / Call to Action -->
        <section class="rounded-3xl bg-gradient-to-r from-slate-900 via-slate-900/90 to-slate-950 border border-slate-800 p-8 sm:p-12 text-center mb-6">
            <div class="max-w-xl mx-auto flex flex-col items-center">
                <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="6" width="20" height="12" rx="2"/>
                        <path d="M6 12h4m-2-2v4m7-2h.01m3 0h.01"/>
                    </svg>
                </div>
                <h2 class="text-2xl sm:text-3xl font-black text-white mb-2">Browse Our Complete Catalog</h2>
                <p class="text-slate-400 text-xs sm:text-sm mb-6 leading-relaxed">
                    Filter by category, search by title, sort by popularity or release date, and enjoy instant HTML5 games anywhere.
                </p>
                <Link
                    :href="route('games.index')"
                    class="px-8 py-3.5 bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-sm uppercase tracking-wide rounded-xl shadow-lg shadow-emerald-500/20 transition-all transform hover:-translate-y-0.5"
                >
                    View All Games
                </Link>
            </div>
        </section>
    </AppLayout>
</template>

