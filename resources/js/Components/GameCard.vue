<script setup lang="ts">
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import type { Game } from '@/types';

const props = withDefaults(
    defineProps<{
        game: Game;
        compact?: boolean;
        priority?: boolean;
        badge?: 'featured' | 'trending' | 'popular' | 'new' | 'none';
        rank?: number;
    }>(),
    {
        compact: false,
        priority: false,
        badge: undefined,
        rank: undefined,
    }
);

const isImageLoaded = ref(false);

const onThumbnailError = (e: Event) => {
    const img = e.target as HTMLImageElement;
    if (img && !img.src.endsWith('/images/thumbnails/default-game.svg')) {
        img.src = '/images/thumbnails/default-game.svg';
    }
};

const onThumbnailLoad = () => {
    isImageLoaded.value = true;
};

/**
 * Format large count metrics cleanly (e.g., 1.2K, 15K, 1.3M).
 */
const formatNumber = (num: number | undefined | null): string => {
    if (!num || num <= 0) return '0';
    if (num >= 1_000_000) {
        const val = (num / 1_000_000).toFixed(1);
        return (val.endsWith('.0') ? val.slice(0, -2) : val) + 'M';
    }
    if (num >= 1_000) {
        const val = (num / 1_000).toFixed(1);
        return (val.endsWith('.0') ? val.slice(0, -2) : val) + 'K';
    }
    return num.toLocaleString();
};
</script>

<template>
    <Link
        :href="route('games.show', { slug: game.slug })"
        class="group relative flex flex-col h-full overflow-hidden rounded-2xl bg-slate-900/90 border border-slate-800/80 hover:border-emerald-500/50 hover:bg-slate-900 hover:-translate-y-1 hover:shadow-xl hover:shadow-emerald-950/30 active:scale-[0.98] transition-all duration-200 ease-out focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950"
    >
        <!-- 1. Thumbnail Container (Consistent 4:3 Aspect Ratio) -->
        <div class="relative w-full aspect-[4/3] overflow-hidden bg-slate-950 shrink-0">
            <!-- Loading Skeleton Shimmer -->
            <div
                v-if="!isImageLoaded"
                class="absolute inset-0 bg-slate-900/80 animate-pulse flex items-center justify-center text-slate-700"
                aria-hidden="true"
            >
                <svg class="w-8 h-8 opacity-40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="2" y="6" width="20" height="12" rx="2" />
                    <circle cx="8" cy="12" r="1.5" />
                    <path d="M14 12h4" />
                </svg>
            </div>

            <!-- Thumbnail Image -->
            <img
                :src="game.thumbnail || '/images/thumbnails/default-game.svg'"
                :alt="`${game.title} thumbnail`"
                width="400"
                height="300"
                :loading="priority ? 'eager' : 'lazy'"
                :fetchpriority="priority ? 'high' : 'auto'"
                decoding="async"
                @load="onThumbnailLoad"
                @error="onThumbnailError"
                class="w-full h-full object-cover object-center transform group-hover:scale-105 group-focus-visible:scale-105 transition-transform duration-300 ease-out"
            />

            <!-- Subtle Contrast Vignette Overlay -->
            <div
                class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-black/20 opacity-70 group-hover:opacity-40 transition-opacity"
                aria-hidden="true"
            ></div>

            <!-- 2. Existing Contextual Badges Row -->
            <div class="absolute top-2 left-2 right-2 flex items-center justify-between gap-1.5 z-10 pointer-events-none">
                <!-- Rank Badge (e.g. Trending #1, #2) or Status Badge -->
                <div class="flex items-center gap-1.5 flex-wrap">
                    <span
                        v-if="rank !== undefined"
                        class="px-2 py-0.5 text-[11px] font-black tracking-tight rounded-md shadow-md flex items-center gap-1 backdrop-blur-sm"
                        :class="
                            rank === 1
                                ? 'bg-amber-400 text-slate-950 ring-1 ring-amber-300'
                                : rank === 2
                                ? 'bg-slate-200 text-slate-950 ring-1 ring-white/60'
                                : rank === 3
                                ? 'bg-amber-700 text-amber-100 ring-1 ring-amber-600'
                                : 'bg-slate-900/90 text-emerald-400 border border-emerald-500/40'
                        "
                    >
                        <span>#{{ rank }}</span>
                    </span>

                    <span
                        v-else-if="badge === 'featured' || (badge === undefined && game.is_featured)"
                        class="px-2 py-0.5 text-[10px] font-bold tracking-wide uppercase bg-amber-500/95 text-slate-950 rounded-md shadow-md backdrop-blur-sm"
                    >
                        Featured
                    </span>

                    <span
                        v-else-if="badge === 'trending' || (badge === undefined && game.is_trending)"
                        class="px-2 py-0.5 text-[10px] font-bold tracking-wide uppercase bg-emerald-500/95 text-slate-950 rounded-md shadow-md backdrop-blur-sm"
                    >
                        Trending
                    </span>

                    <span
                        v-else-if="badge === 'popular' || (badge === undefined && game.is_popular)"
                        class="px-2 py-0.5 text-[10px] font-bold tracking-wide uppercase bg-purple-500/95 text-white rounded-md shadow-md backdrop-blur-sm"
                    >
                        Popular
                    </span>

                    <span
                        v-else-if="badge === 'new'"
                        class="px-2 py-0.5 text-[10px] font-bold tracking-wide uppercase bg-cyan-500/95 text-slate-950 rounded-md shadow-md backdrop-blur-sm"
                    >
                        New
                    </span>
                </div>

                <!-- Category Pill (Top-Right) -->
                <span
                    v-if="game.category?.name"
                    class="ml-auto px-2 py-0.5 text-[10px] font-semibold tracking-wide bg-slate-950/80 backdrop-blur-md text-slate-300 border border-slate-700/60 rounded-md shadow-sm truncate max-w-[110px]"
                >
                    {{ game.category.name }}
                </span>
            </div>

            <!-- Hover Play Button Overlay -->
            <div
                class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 group-focus-visible:opacity-100 transition-all duration-200 pointer-events-none"
                aria-hidden="true"
            >
                <div
                    class="w-11 h-11 rounded-full bg-emerald-500 text-slate-950 flex items-center justify-center shadow-lg shadow-emerald-500/50 transform scale-75 group-hover:scale-100 group-focus-visible:scale-100 transition-transform duration-200 ease-out"
                >
                    <svg class="w-5 h-5 ml-0.5" viewBox="0 0 24 24" fill="currentColor">
                        <polygon points="6 3 20 12 6 21 6 3" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Meta Content Area (No Descriptions / Excerpts) -->
        <div class="flex flex-col p-3 bg-slate-900/95 flex-grow justify-between gap-2.5">
            <div>
                <!-- 3. Game Title -->
                <h3
                    class="font-bold text-xs sm:text-sm text-slate-100 group-hover:text-emerald-400 group-focus-visible:text-emerald-400 transition-colors line-clamp-1 tracking-tight"
                >
                    {{ game.title }}
                </h3>

                <!-- 4. Game Rating (Actual user rating or graceful Unrated indicator) -->
                <div class="mt-1 flex items-center gap-1.5 text-[11px]">
                    <template v-if="game.rating && game.rating_count && game.rating_count > 0">
                        <div class="flex items-center text-amber-400" aria-hidden="true">
                            <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                            </svg>
                        </div>
                        <span class="font-bold text-slate-200">{{ Number(game.rating).toFixed(1) }}</span>
                        <span class="text-slate-400 font-normal">({{ formatNumber(game.rating_count) }})</span>
                    </template>
                    <template v-else>
                        <div class="flex items-center text-slate-500" aria-hidden="true">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                            </svg>
                        </div>
                        <span class="text-slate-400 font-medium">Unrated</span>
                    </template>
                </div>
            </div>

            <!-- Card Bottom Bar: 5. Total Plays & 6. Currently Playing Players -->
            <div class="flex items-center justify-between text-[11px] text-slate-400 pt-2 border-t border-slate-800/60 gap-1.5">
                <!-- 5. Total Plays -->
                <span
                    class="inline-flex items-center gap-1 text-slate-300 font-medium truncate"
                    :title="`${game.plays_count ?? 0} total verified plays`"
                >
                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                    <span class="truncate">{{ formatNumber(game.plays_count ?? 0) }} Plays</span>
                </span>

                <!-- 6. Currently Playing Players (Live Active Sessions) -->
                <span
                    class="inline-flex items-center gap-1.5 shrink-0 text-slate-400 font-medium"
                    :title="`${game.playing_now ?? 0} active player${(game.playing_now ?? 0) === 1 ? '' : 's'} in last 90s`"
                >
                    <span
                        class="w-1.5 h-1.5 rounded-full shrink-0"
                        :class="game.playing_now && game.playing_now > 0 ? 'bg-emerald-400 shadow-sm shadow-emerald-400 animate-pulse' : 'bg-slate-600'"
                        aria-hidden="true"
                    ></span>
                    <span :class="game.playing_now && game.playing_now > 0 ? 'text-emerald-300 font-semibold' : 'text-slate-400'">
                        {{ game.playing_now ?? 0 }} <span class="hidden sm:inline">Playing</span> Now
                    </span>
                </span>
            </div>
        </div>
    </Link>
</template>
