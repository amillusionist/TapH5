<script setup lang="ts">
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import type { Game } from '@/types';

const props = defineProps<{
    games: Game[];
}>();

const activeIndex = ref(0);

const selectGame = (index: number) => {
    activeIndex.value = index;
};

const nextSlide = () => {
    if (props.games.length <= 1) return;
    activeIndex.value = (activeIndex.value + 1) % props.games.length;
};

const prevSlide = () => {
    if (props.games.length <= 1) return;
    activeIndex.value = (activeIndex.value - 1 + props.games.length) % props.games.length;
};

const onThumbnailError = (e: Event) => {
    const img = e.target as HTMLImageElement;
    if (img && !img.src.endsWith('/images/thumbnails/default-game.svg')) {
        img.src = '/images/thumbnails/default-game.svg';
    }
};
</script>

<template>
    <section
        v-if="games.length > 0"
        class="relative w-full overflow-hidden bg-slate-950 border-b border-slate-800/80"
        aria-label="Featured Games Hero Showcase"
    >
        <div
            v-for="(game, index) in games"
            :key="game.id"
            v-show="index === activeIndex"
            class="relative w-full transition-opacity duration-700 ease-in-out"
        >
            <!-- Full-Bleed Edge-to-Edge Artwork Background -->
            <div class="absolute inset-0 overflow-hidden select-none pointer-events-none">
                <img
                    :src="game.thumbnail || '/images/thumbnails/default-game.svg'"
                    :alt="`${game.title} background artwork`"
                    width="1920"
                    height="800"
                    fetchpriority="high"
                    loading="eager"
                    decoding="async"
                    @error="onThumbnailError"
                    class="w-full h-full object-cover object-center transform scale-105 transition-transform duration-1000 ease-out"
                />

                <!-- High-contrast directional gradients for flawless readability across all screen widths -->
                <div class="absolute inset-0 bg-gradient-to-r from-[#080d1a] via-[#080d1a]/85 via-45% to-transparent sm:via-55% lg:via-60%"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-[#080d1a] via-[#080d1a]/60 via-20% to-transparent"></div>
                <div class="absolute inset-0 bg-gradient-to-b from-[#080d1a]/70 via-transparent to-transparent"></div>
            </div>

            <!-- Grid-Aligned Foreground Content Container -->
            <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full min-h-[460px] sm:min-h-[520px] lg:min-h-[580px] xl:min-h-[620px] flex flex-col justify-center py-12 sm:py-16 lg:py-20 pb-20 sm:pb-16">
                <div class="max-w-2xl lg:max-w-3xl">
                    <!-- Badges Row -->
                    <div class="flex flex-wrap items-center gap-2 mb-3 sm:mb-4">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-bold tracking-wider uppercase bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 rounded-full shadow-sm backdrop-blur-sm">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            Featured Spotlight
                        </span>

                        <span
                            v-if="game.category"
                            class="px-3 py-1 text-xs font-semibold tracking-wider uppercase bg-slate-900/80 text-slate-300 border border-slate-700/60 rounded-full backdrop-blur-sm"
                        >
                            {{ game.category.name }}
                        </span>

                        <span class="hidden sm:inline-flex items-center gap-1 px-3 py-1 text-xs font-medium text-slate-400 bg-slate-950/60 border border-slate-800/80 rounded-full">
                            <svg class="w-3.5 h-3.5 text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>
                            </svg>
                            Instant Play • No Download
                        </span>
                    </div>

                    <!-- Title -->
                    <h1 class="text-3xl sm:text-5xl md:text-6xl lg:text-7xl font-black text-white tracking-tight leading-none mb-3 sm:mb-4 drop-shadow-md">
                        {{ game.title }}
                    </h1>

                    <!-- Description -->
                    <p class="text-slate-300 text-sm sm:text-base lg:text-lg leading-relaxed line-clamp-3 mb-6 sm:mb-8 max-w-xl sm:max-w-2xl drop-shadow">
                        {{ game.description || 'Experience instant, high-performance HTML5 gameplay directly in your browser.' }}
                    </p>

                    <!-- Call to Action Buttons -->
                    <div class="flex flex-wrap items-center gap-3 sm:gap-4">
                        <Link
                            :href="route('games.show', { slug: game.slug })"
                            class="px-7 sm:px-9 py-3.5 sm:py-4 bg-gradient-to-r from-emerald-500 via-teal-400 to-cyan-400 hover:from-emerald-400 hover:to-cyan-300 text-slate-950 font-black tracking-wide uppercase text-sm sm:text-base rounded-2xl shadow-xl shadow-emerald-500/25 flex items-center gap-2.5 transform hover:-translate-y-0.5 active:translate-y-0 transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950"
                        >
                            <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24" aria-hidden="true">
                                <polygon points="6 3 20 12 6 21 6 3" />
                            </svg>
                            <span>Play Now Free</span>
                        </Link>

                        <Link
                            v-if="game.category"
                            :href="route('categories.show', { slug: game.category.slug })"
                            class="px-5 sm:px-6 py-3.5 sm:py-4 bg-slate-900/80 hover:bg-slate-800 text-slate-200 hover:text-white border border-slate-700/80 font-bold text-sm sm:text-base rounded-2xl transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950 flex items-center gap-2 backdrop-blur-sm"
                        >
                            <span>More {{ game.category.name }}</span>
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m9 18 6-6-6-6"/>
                            </svg>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Global Slide Navigation Controls (Fixed over the hero corner) -->
        <div v-if="games.length > 1" class="pointer-events-none absolute inset-x-0 bottom-5 sm:bottom-8 lg:bottom-10 z-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-end">
                <div class="pointer-events-auto flex items-center gap-2 sm:gap-2.5">
                    <!-- Prev Button -->
                    <button
                        @click="prevSlide"
                        class="p-2 sm:p-2.5 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 shadow-lg transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400"
                        aria-label="Previous featured game slide"
                        title="Previous Slide"
                    >
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path d="m15 18-6-6 6-6"/>
                        </svg>
                    </button>

                    <!-- Slide Dots -->
                    <div class="flex items-center gap-1.5 px-2 py-1 rounded-xl bg-slate-900/80 border border-slate-800/80 shadow-lg backdrop-blur-sm">
                        <button
                            v-for="(_, dotIndex) in games"
                            :key="dotIndex"
                            @click="selectGame(dotIndex)"
                            class="p-2 flex items-center justify-center rounded-full transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400"
                            :aria-label="`Slide ${dotIndex + 1}: ${games[dotIndex]?.title}`"
                            :title="`Slide ${dotIndex + 1}`"
                        >
                            <span
                                class="h-2 rounded-full transition-all duration-300 block"
                                :class="dotIndex === activeIndex ? 'w-7 bg-emerald-400 shadow-sm shadow-emerald-400/50' : 'w-2 bg-slate-600 hover:bg-slate-400'"
                            ></span>
                        </button>
                    </div>

                    <!-- Next Button -->
                    <button
                        @click="nextSlide"
                        class="p-2 sm:p-2.5 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 shadow-lg transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400"
                        aria-label="Next featured game slide"
                        title="Next Slide"
                    >
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path d="m9 18 6-6-6-6"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </section>
</template>
