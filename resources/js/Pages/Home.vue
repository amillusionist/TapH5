<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import HeroBanner from '@/Components/HeroBanner.vue';
import GameCard from '@/Components/GameCard.vue';
import CategoryCard from '@/Components/CategoryCard.vue';
import AdSlot from '@/Components/Ads/AdSlot.vue';
import SeoHead from '@/Components/SeoHead.vue';
import EmptyState from '@/Components/EmptyState.vue';
import type { Category, Game, HomepageSection, SiteMeta } from '@/types';

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
    sections?: Record<string, HomepageSection>;
}>();
</script>

<template>
    <SeoHead :meta="meta" />

    <AppLayout>
        <!-- Full-Width Hero Section -->
        <template #hero>
            <HeroBanner v-if="!sections?.hero || sections.hero.is_enabled" :games="heroGames" />
        </template>

        <!-- Monetization: Top Leaderboard Placement -->
        <AdSlot placement-key="home_top" class-name="mb-10 sm:mb-12" />

        <!-- 1. Categories / Genres Bar -->
        <section v-if="!sections?.genres || sections.genres.is_enabled" class="mb-12 sm:mb-16">
            <div class="flex items-center justify-between mb-5 sm:mb-6">
                <div>
                    <h2 class="text-lg sm:text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-cyan-400 ring-4 ring-cyan-500/20"></span>
                        {{ sections?.genres?.title || 'Explore Game Genres' }}
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">
                        {{ sections?.genres?.subtitle || 'Browse our collection organized by gameplay mechanics and genres' }}
                    </p>
                </div>
            </div>

            <div v-if="categories.length > 0" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2.5 sm:gap-3.5">
                <CategoryCard
                    v-for="cat in categories"
                    :key="cat.id"
                    :category="cat"
                />
            </div>
            <EmptyState v-else title="No Categories Available" message="Categories will appear here once configured." />
        </section>

        <!-- 2. Featured Games (Curated Spotlight) -->
        <section v-if="(!sections?.featured || sections.featured.is_enabled) && featuredGames.length > 0" class="mb-12 sm:mb-16">
            <div class="flex items-center justify-between mb-5 sm:mb-6">
                <div>
                    <h2 class="text-lg sm:text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-400 ring-4 ring-amber-500/20"></span>
                        {{ sections?.featured?.title || 'Featured Spotlight' }}
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">
                        {{ sections?.featured?.subtitle || 'Handpicked premiere titles curated for immediate in-browser play' }}
                    </p>
                </div>
                <Link
                    :href="route('games.index', { sort: 'featured' })"
                    class="text-xs font-bold uppercase tracking-wider text-emerald-400 hover:text-emerald-300 flex items-center gap-1 group shrink-0 focus:outline-none focus:underline"
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
                    badge="featured"
                    :priority="idx < 4"
                />
            </div>
        </section>

        <!-- 3. Trending Games (Ranked Leaderboard Style) -->
        <section v-if="(!sections?.trending || sections.trending.is_enabled) && trendingGames.length > 0" class="mb-12 sm:mb-16">
            <div class="flex items-center justify-between mb-5 sm:mb-6">
                <div>
                    <h2 class="text-lg sm:text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 ring-4 ring-emerald-500/20"></span>
                        {{ sections?.trending?.title || 'Trending Now' }}
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">
                        {{ sections?.trending?.subtitle || 'Top games capturing player attention and active gameplay today' }}
                    </p>
                </div>
                <Link
                    :href="route('games.index', { sort: 'popular' })"
                    class="text-xs font-bold uppercase tracking-wider text-emerald-400 hover:text-emerald-300 flex items-center gap-1 group shrink-0 focus:outline-none focus:underline"
                >
                    <span>Leaderboard</span>
                    <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14m-7-7 7 7-7 7"/>
                    </svg>
                </Link>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 sm:gap-4">
                <GameCard
                    v-for="(game, idx) in trendingGames"
                    :key="game.id"
                    :game="game"
                    :rank="idx + 1"
                />
            </div>
        </section>

        <!-- Monetization: Between Sections Placement -->
        <AdSlot placement-key="home_between_sections" class-name="mb-12 sm:mb-16" />

        <!-- 4. New Releases -->
        <section v-if="(!sections?.new || sections.new.is_enabled) && newGames.length > 0" class="mb-12 sm:mb-16">
            <div class="flex items-center justify-between mb-5 sm:mb-6">
                <div>
                    <h2 class="text-lg sm:text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-cyan-400 ring-4 ring-cyan-500/20"></span>
                        {{ sections?.new?.title || 'New Releases' }}
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">
                        {{ sections?.new?.subtitle || 'Fresh arrivals recently published to the TapH5 catalog' }}
                    </p>
                </div>
                <Link
                    :href="route('games.index', { sort: 'newest' })"
                    class="text-xs font-bold uppercase tracking-wider text-emerald-400 hover:text-emerald-300 flex items-center gap-1 group shrink-0 focus:outline-none focus:underline"
                >
                    <span>View All New</span>
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
                    badge="new"
                />
            </div>
        </section>

        <!-- 5. Popular Games (Community Favorites) -->
        <section v-if="(!sections?.popular || sections.popular.is_enabled) && popularGames.length > 0" class="mb-12 sm:mb-16">
            <div class="flex items-center justify-between mb-5 sm:mb-6">
                <div>
                    <h2 class="text-lg sm:text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-purple-400 ring-4 ring-purple-500/20"></span>
                        {{ sections?.popular?.title || 'Popular Games' }}
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">
                        {{ sections?.popular?.subtitle || 'Community favorites across all genres and platforms' }}
                    </p>
                </div>
                <Link
                    :href="route('games.index', { sort: 'popular' })"
                    class="text-xs font-bold uppercase tracking-wider text-emerald-400 hover:text-emerald-300 flex items-center gap-1 group shrink-0 focus:outline-none focus:underline"
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
                    badge="popular"
                />
            </div>
        </section>

        <!-- 6. Recently Added Section -->
        <section v-if="(!sections?.recently_added || sections.recently_added.is_enabled) && recentlyAdded.length > 0" class="mb-12 sm:mb-16">
            <div class="flex items-center justify-between mb-5 sm:mb-6">
                <div>
                    <h2 class="text-lg sm:text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-rose-400 ring-4 ring-rose-500/20"></span>
                        {{ sections?.recently_added?.title || 'Recently Cataloged' }}
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">
                        {{ sections?.recently_added?.subtitle || 'Latest verified HTML5 titles added to our gaming library' }}
                    </p>
                </div>
                <Link
                    :href="route('games.index', { sort: 'newest' })"
                    class="text-xs font-bold uppercase tracking-wider text-emerald-400 hover:text-emerald-300 flex items-center gap-1 group shrink-0 focus:outline-none focus:underline"
                >
                    <span>View All</span>
                    <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14m-7-7 7 7-7 7"/>
                    </svg>
                </Link>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 sm:gap-4">
                <GameCard
                    v-for="game in recentlyAdded"
                    :key="game.id"
                    :game="game"
                />
            </div>
        </section>

        <!-- 7. All Games Catalog Call-To-Action Banner -->
        <section v-if="!sections?.catalog_cta || sections.catalog_cta.is_enabled" class="rounded-3xl bg-gradient-to-br from-slate-900 via-slate-900/95 to-slate-950 border border-slate-800 p-7 sm:p-12 text-center mb-6 shadow-2xl relative overflow-hidden">
            <!-- Subtle background ambient glow -->
            <div class="absolute -top-24 left-1/2 -translate-x-1/2 w-96 h-96 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none" aria-hidden="true"></div>

            <div class="max-w-xl mx-auto flex flex-col items-center relative z-10">
                <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 flex items-center justify-center mb-4 shadow-inner">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="6" width="20" height="12" rx="2"/>
                        <path d="M6 12h4m-2-2v4m7-2h.01m3 0h.01"/>
                    </svg>
                </div>

                <h2 class="text-2xl sm:text-3xl font-black text-white mb-2.5 tracking-tight">
                    {{ sections?.catalog_cta?.title || 'Browse Our Complete Game Catalog' }}
                </h2>

                <p class="text-slate-400 text-xs sm:text-sm mb-6 leading-relaxed max-w-md">
                    {{ sections?.catalog_cta?.subtitle || 'Filter by gameplay genre, search by title, sort by popularity or release date, and enjoy instant HTML5 games across all devices.' }}
                </p>

                <!-- Value Highlights -->
                <div class="flex flex-wrap items-center justify-center gap-3 mb-7 text-xs text-slate-300">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-800/80 border border-slate-700/60">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                        Zero Installs
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-800/80 border border-slate-700/60">
                        <span class="w-1.5 h-1.5 rounded-full bg-cyan-400"></span>
                        Touch & Keyboard
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-800/80 border border-slate-700/60">
                        <span class="w-1.5 h-1.5 rounded-full bg-purple-400"></span>
                        100% Free
                    </span>
                </div>

                <Link
                    :href="route('games.index')"
                    class="px-8 py-3.5 bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-sm uppercase tracking-wide rounded-xl shadow-lg shadow-emerald-500/25 transition-all transform hover:-translate-y-0.5 active:translate-y-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950"
                >
                    View All Games
                </Link>
            </div>
        </section>
    </AppLayout>
</template>
