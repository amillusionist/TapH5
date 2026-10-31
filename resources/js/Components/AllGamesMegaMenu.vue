<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import type { Game, PageProps } from '@/types';

const props = withDefaults(
    defineProps<{
        isOpen: boolean;
        games?: Game[];
    }>(),
    {
        isOpen: false,
        games: undefined,
    }
);

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'mouseenter'): void;
    (e: 'mouseleave'): void;
}>();

const page = usePage<PageProps>();
const carouselTrack = ref<HTMLElement | null>(null);
const canScrollLeft = ref(false);
const canScrollRight = ref(false);

const loadedImages = ref<Record<number, boolean>>({});

const onThumbnailLoad = (id: number) => {
    loadedImages.value[id] = true;
};

const onThumbnailError = (e: Event) => {
    const img = e.target as HTMLImageElement;
    if (img && !img.src.endsWith('/images/thumbnails/default-game.svg')) {
        img.src = '/images/thumbnails/default-game.svg';
    }
};

const updateScrollBounds = () => {
    const el = carouselTrack.value;
    if (!el) {
        canScrollLeft.value = false;
        canScrollRight.value = false;
        return;
    }
    canScrollLeft.value = el.scrollLeft > 5;
    canScrollRight.value = el.scrollLeft < el.scrollWidth - el.clientWidth - 5;
};

const scrollCarousel = (direction: 'left' | 'right') => {
    const el = carouselTrack.value;
    if (!el) return;

    // Scroll by approximately 2.5 cards or 70% of visible width
    const scrollAmount = Math.max(220, Math.round(el.clientWidth * 0.7));
    const targetLeft = direction === 'left' ? el.scrollLeft - scrollAmount : el.scrollLeft + scrollAmount;

    el.scrollTo({
        left: targetLeft,
        behavior: 'smooth',
    });
};

const handleKeyDown = (e: KeyboardEvent) => {
    if (props.isOpen && e.key === 'Escape') {
        emit('close');
    }
};

watch(
    () => props.isOpen,
    (open) => {
        if (open) {
            nextTick(() => {
                updateScrollBounds();
            });
        }
    }
);

onMounted(() => {
    window.addEventListener('keydown', handleKeyDown);
    window.addEventListener('resize', updateScrollBounds);
    nextTick(() => {
        updateScrollBounds();
    });
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyDown);
    window.removeEventListener('resize', updateScrollBounds);
});

// Use passed prop games, or shared page.props.megaMenuGames, or empty array
const activeGames = computed(() => {
    if (props.games && props.games.length > 0) return props.games;
    if (page.props.megaMenuGames && page.props.megaMenuGames.length > 0) return page.props.megaMenuGames;
    return [];
});
</script>

<template>
    <transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="transform opacity-0 -translate-y-2"
        enter-to-class="transform opacity-100 translate-y-0"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="transform opacity-100 translate-y-0"
        leave-to-class="transform opacity-0 -translate-y-2"
    >
        <div
            v-show="isOpen"
            id="all-games-mega-menu"
            role="region"
            aria-label="All Games Mega Menu"
            class="absolute top-full left-0 right-0 w-full bg-slate-950/98 backdrop-blur-2xl border-b border-slate-800 shadow-2xl z-40 focus:outline-none"
            @mouseenter="emit('mouseenter')"
            @mouseleave="emit('mouseleave')"
        >
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5 sm:py-6">
                <!-- Header Controls Row -->
                <div class="flex items-center justify-between mb-4 gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2" y="6" width="20" height="12" rx="2" />
                                <path d="M6 12h4m-2-2v4m7-2h.01m3 0h.01" />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm sm:text-base font-black text-white tracking-tight">
                                    Popular Games
                                </h3>
                                <span class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 rounded-md">
                                    Instant Play
                                </span>
                            </div>
                            <p class="text-xs text-slate-400 hidden sm:block mt-0.5">
                                Top-rated HTML5 titles ready for immediate in-browser play
                            </p>
                        </div>
                    </div>

                    <!-- Right Actions: Carousel Controls & View All Link -->
                    <div class="flex items-center gap-2 sm:gap-3">
                        <!-- Carousel Prev Button -->
                        <button
                            type="button"
                            aria-label="Previous games"
                            :disabled="!canScrollLeft"
                            @click="scrollCarousel('left')"
                            class="p-2 rounded-xl bg-slate-900 border border-slate-800 text-slate-300 hover:text-white hover:border-emerald-500/40 disabled:opacity-30 disabled:pointer-events-none transition-all focus:outline-none focus:ring-1 focus:ring-emerald-400"
                        >
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m15 18-6-6 6-6" />
                            </svg>
                        </button>

                        <!-- Carousel Next Button -->
                        <button
                            type="button"
                            aria-label="Next games"
                            :disabled="!canScrollRight"
                            @click="scrollCarousel('right')"
                            class="p-2 rounded-xl bg-slate-900 border border-slate-800 text-slate-300 hover:text-white hover:border-emerald-500/40 disabled:opacity-30 disabled:pointer-events-none transition-all focus:outline-none focus:ring-1 focus:ring-emerald-400"
                        >
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m9 18 6-6-6-6" />
                            </svg>
                        </button>

                        <div class="h-4 w-px bg-slate-800 hidden sm:block"></div>

                        <!-- Prominent View All Games Link -->
                        <Link
                            :href="route('games.index')"
                            @click="emit('close')"
                            class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 hover:text-emerald-300 font-bold text-xs border border-emerald-500/30 transition-all hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-emerald-400 shrink-0"
                        >
                            <span>View All Games</span>
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M5 12h14m-7-7 7 7-7 7" />
                            </svg>
                        </Link>
                    </div>
                </div>

                <!-- Carousel Track of Game Cards -->
                <div
                    ref="carouselTrack"
                    @scroll="updateScrollBounds"
                    class="flex items-stretch gap-3.5 sm:gap-4 overflow-x-auto scrollbar-none scroll-smooth pb-2 pt-1 focus:outline-none"
                    tabindex="0"
                    aria-label="Games Carousel"
                >
                    <Link
                        v-for="game in activeGames"
                        :key="game.id"
                        :href="route('games.show', { slug: game.slug })"
                        @click="emit('close')"
                        class="group relative flex flex-col w-40 sm:w-48 lg:w-52 shrink-0 rounded-2xl bg-slate-900/90 border border-slate-800/80 hover:border-emerald-500/50 hover:bg-slate-900 hover:-translate-y-1 hover:shadow-xl hover:shadow-emerald-950/40 active:scale-[0.98] transition-all duration-200 ease-out focus:outline-none focus:ring-2 focus:ring-emerald-400 overflow-hidden"
                    >
                        <!-- Thumbnail Visual (4:3) -->
                        <div class="relative w-full aspect-[4/3] bg-slate-950 overflow-hidden shrink-0">
                            <!-- Skeleton Shimmer Placeholder -->
                            <div
                                v-if="!loadedImages[game.id]"
                                class="absolute inset-0 bg-slate-900/80 animate-pulse flex items-center justify-center text-slate-700"
                                aria-hidden="true"
                            >
                                <svg class="w-6 h-6 opacity-40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <rect x="2" y="6" width="20" height="12" rx="2" />
                                    <circle cx="8" cy="12" r="1.5" />
                                    <path d="M14 12h4" />
                                </svg>
                            </div>

                            <img
                                :src="game.thumbnail || '/images/thumbnails/default-game.svg'"
                                :alt="`${game.title} thumbnail`"
                                width="400"
                                height="300"
                                loading="lazy"
                                decoding="async"
                                @load="onThumbnailLoad(game.id)"
                                @error="onThumbnailError"
                                class="w-full h-full object-cover object-center transform group-hover:scale-105 group-focus-visible:scale-105 transition-transform duration-300 ease-out"
                            />

                            <!-- Vignette Gradient -->
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-transparent opacity-60 group-hover:opacity-40 transition-opacity"></div>

                            <!-- Centered Play Icon Hover Overlay -->
                            <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 group-focus-visible:opacity-100 transition-all duration-200 pointer-events-none">
                                <div class="w-10 h-10 rounded-full bg-emerald-500 text-slate-950 flex items-center justify-center shadow-lg shadow-emerald-500/50 transform scale-75 group-hover:scale-100 transition-transform duration-200 ease-out">
                                    <svg class="w-4 h-4 ml-0.5" viewBox="0 0 24 24" fill="currentColor">
                                        <polygon points="6 3 20 12 6 21 6 3" />
                                    </svg>
                                </div>
                            </div>

                            <!-- Category Badge (Top-Right) -->
                            <div class="absolute top-2 right-2">
                                <span
                                    v-if="game.category?.name"
                                    class="px-2 py-0.5 text-[9px] font-semibold tracking-wide bg-slate-950/80 backdrop-blur-md text-slate-300 border border-slate-700/60 rounded-md shadow-sm"
                                >
                                    {{ game.category.name }}
                                </span>
                            </div>
                        </div>

                        <!-- Card Meta Information -->
                        <div class="flex flex-col p-2.5 sm:p-3 bg-slate-900/95 flex-grow justify-between gap-1.5">
                            <div>
                                <h4 class="font-bold text-xs sm:text-sm text-slate-100 group-hover:text-emerald-400 transition-colors line-clamp-1 tracking-tight">
                                    {{ game.title }}
                                </h4>
                            </div>

                            <div class="flex items-center justify-between text-[10px] text-slate-400 pt-1.5 border-t border-slate-800/60">
                                <span class="text-slate-300 font-medium inline-flex items-center gap-1">
                                    <svg class="w-3 h-3 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polygon points="6 3 20 12 6 21 6 3" />
                                    </svg>
                                    <span>Play Now</span>
                                </span>
                                <span v-if="game.plays_count && game.plays_count > 0" class="text-slate-400">
                                    {{ game.plays_count }} plays
                                </span>
                                <span v-else class="text-slate-400">
                                    Free H5
                                </span>
                            </div>
                        </div>
                    </Link>

                    <!-- Empty State Fallback -->
                    <div
                        v-if="activeGames.length === 0"
                        class="w-full py-8 text-center text-slate-400 text-xs"
                    >
                        No games currently available in the mega menu catalog.
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>
