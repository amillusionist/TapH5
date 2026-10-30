<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import GamePlayer from '@/Components/GamePlayer.vue';
import GameCard from '@/Components/GameCard.vue';
import AdSlot from '@/Components/Ads/AdSlot.vue';
import SeoHead from '@/Components/SeoHead.vue';
import type { Game, SiteMeta } from '@/types';

defineProps<{
    game: Game;
    relatedGames: Game[];
    meta: SiteMeta;
}>();
</script>

<template>
    <SeoHead :meta="meta" />

    <AppLayout>
        <!-- Breadcrumb Navigation -->
        <nav class="flex items-center gap-2 text-xs text-slate-400 mb-4">
            <Link href="/" class="hover:text-emerald-400 transition-colors">Home</Link>
            <span>/</span>
            <Link :href="route('games.index')" class="hover:text-emerald-400 transition-colors">Games</Link>
            <span v-if="game.category">/</span>
            <Link
                v-if="game.category"
                :href="route('categories.show', { slug: game.category.slug })"
                class="hover:text-emerald-400 transition-colors"
            >
                {{ game.category.name }}
            </Link>
            <span>/</span>
            <span class="text-slate-200 font-medium truncate max-w-[200px]">{{ game.title }}</span>
        </nav>

        <!-- Monetization: Top Leaderboard Placement -->
        <AdSlot placement-key="game_detail_top" class-name="mb-6" />

        <!-- Main Game Stage Section -->
        <div class="mb-8">
            <!-- Dedicated Reusable Sandboxed Game Player -->
            <GamePlayer :game="game" :auto-play="false" />

            <!-- Monetization: Below Player Placement -->
            <AdSlot placement-key="game_player_below" class-name="mt-6" />
        </div>

        <!-- Game Information and Details -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-14">
            <!-- Left 2 Cols: Title, Details, Instructions -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Header Card -->
                <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800">
                    <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-14 h-14 rounded-xl overflow-hidden bg-slate-950 border border-slate-800 shrink-0">
                                <img
                                    :src="game.thumbnail || '/images/thumbnails/neon-pulse.svg'"
                                    :alt="`${game.title} thumbnail`"
                                    width="56"
                                    height="56"
                                    loading="eager"
                                    decoding="async"
                                    class="w-full h-full object-cover"
                                />
                            </div>
                            <div>
                                <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">{{ game.title }}</h1>
                                <div class="flex items-center gap-2 mt-1">
                                    <Link
                                        v-if="game.category"
                                        :href="route('categories.show', { slug: game.category.slug })"
                                        class="px-2 py-0.5 text-xs font-semibold text-emerald-400 bg-emerald-500/10 border border-emerald-500/30 rounded-md hover:bg-emerald-500/20 transition-colors"
                                    >
                                        {{ game.category.name }}
                                    </Link>
                                    <span class="text-xs text-slate-400">
                                        {{ game.game_type === 'multi_file' ? 'Multi-File HTML5' : 'Single-File HTML5' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Back Button -->
                        <Link
                            :href="route('games.index')"
                            class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-semibold flex items-center gap-1.5 transition-colors"
                        >
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m15 18-6-6 6-6"/>
                            </svg>
                            <span>Back to Games</span>
                        </Link>
                    </div>

                    <!-- Description -->
                    <div class="pt-4 border-t border-slate-800/80">
                        <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">About The Game</h2>
                        <p class="text-sm text-slate-300 leading-relaxed whitespace-pre-line">
                            {{ game.description || 'No detailed description available for this game yet.' }}
                        </p>
                    </div>

                    <!-- Instructions / Controls -->
                    <div v-if="game.instructions" class="mt-6 pt-4 border-t border-slate-800/80">
                        <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>
                            </svg>
                            <span>How To Play & Controls</span>
                        </h2>
                        <div class="p-3.5 rounded-xl bg-slate-950/60 border border-slate-800 text-xs text-slate-300 leading-relaxed">
                            {{ game.instructions }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right 1 Col: Game Specifications & Tags -->
            <div class="space-y-6">
                <!-- Meta specs -->
                <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 space-y-4">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Game Specifications</h3>

                    <div class="space-y-3 text-xs">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-800/60">
                            <span class="text-slate-400">Platform</span>
                            <span class="font-medium text-slate-200">HTML5 Web (Browser)</span>
                        </div>
                        <div class="flex items-center justify-between pb-2 border-b border-slate-800/60">
                            <span class="text-slate-400">Compatibility</span>
                            <span class="font-medium text-slate-200">Mobile, Tablet, Desktop</span>
                        </div>
                        <div class="flex items-center justify-between pb-2 border-b border-slate-800/60">
                            <span class="text-slate-400">Sandbox Isolation</span>
                            <span class="font-medium text-emerald-400">Sandboxed Game</span>
                        </div>
                        <div class="flex items-center justify-between pb-2 border-b border-slate-800/60">
                            <span class="text-slate-400">Distribution</span>
                            <span class="font-medium text-slate-200">Free / No Download</span>
                        </div>
                    </div>

                    <!-- Tags -->
                    <div v-if="game.tags && game.tags.length > 0" class="pt-3">
                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2.5">Tags</h4>
                        <div class="flex flex-wrap gap-1.5">
                            <span
                                v-for="tag in game.tags"
                                :key="tag.id"
                                class="px-2.5 py-1 text-[11px] font-medium bg-slate-800/80 text-slate-300 rounded-lg border border-slate-700/60"
                            >
                                #{{ tag.name }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monetization: Description Footer Placement -->
        <AdSlot placement-key="game_detail_content" class-name="mb-14" />

        <!-- Related Games Section -->
        <section v-if="relatedGames.length > 0" class="mb-12">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                        Related Games
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">More titles you might enjoy playing next</p>
                </div>
                <Link
                    v-if="game.category"
                    :href="route('categories.show', { slug: game.category.slug })"
                    class="text-xs font-bold uppercase tracking-wider text-emerald-400 hover:text-emerald-300 flex items-center gap-1 group"
                >
                    <span>More {{ game.category.name }}</span>
                    <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14m-7-7 7 7-7 7"/>
                    </svg>
                </Link>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                <GameCard
                    v-for="(related, idx) in relatedGames"
                    :key="related.id"
                    :game="related"
                    :priority="idx < 4"
                />
            </div>
        </section>
    </AppLayout>
</template>

