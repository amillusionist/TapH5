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
</script>

<template>
    <div v-if="games.length > 0" class="relative w-full overflow-hidden rounded-3xl bg-slate-900 border border-slate-800 shadow-2xl mb-12">
        <div
            v-for="(game, index) in games"
            :key="game.id"
            v-show="index === activeIndex"
            class="relative min-h-[380px] sm:min-h-[460px] md:min-h-[500px] flex flex-col justify-end p-6 sm:p-10 md:p-14 overflow-hidden transition-all duration-700"
        >
            <!-- Background Image with deep cinematic overlay -->
            <div class="absolute inset-0 overflow-hidden">
                <img
                    v-if="game.thumbnail"
                    :src="game.thumbnail"
                    :alt="game.title"
                    width="1280"
                    height="600"
                    fetchpriority="high"
                    loading="eager"
                    decoding="async"
                    class="w-full h-full object-cover object-center transition-all duration-700 transform scale-105"
                />
                <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/80 to-slate-950/40"></div>
                <div class="absolute inset-0 bg-gradient-to-r from-slate-950 via-slate-950/90 to-transparent"></div>
            </div>

            <!-- Content Area -->
            <div class="relative z-10 max-w-2xl">
                <!-- Badges -->
                <div class="flex items-center gap-2 mb-3">
                    <span class="px-2.5 py-1 text-xs font-bold tracking-wider uppercase bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 rounded-lg">
                        Featured Title
                    </span>
                    <span v-if="game.category" class="px-2.5 py-1 text-xs font-semibold tracking-wider uppercase bg-slate-800/80 text-slate-300 rounded-lg">
                        {{ game.category.name }}
                    </span>
                </div>

                <!-- Title -->
                <h2 class="text-3xl sm:text-5xl md:text-6xl font-black text-white tracking-tight leading-none mb-4 drop-shadow-md">
                    {{ game.title }}
                </h2>

                <!-- Description -->
                <p class="text-slate-300 text-sm sm:text-base leading-relaxed line-clamp-3 mb-6 max-w-xl">
                    {{ game.description }}
                </p>

                <!-- Actions -->
                <div class="flex flex-wrap items-center gap-4">
                    <Link
                        :href="route('games.show', { slug: game.slug })"
                        class="px-8 py-3.5 bg-gradient-to-r from-emerald-500 via-teal-400 to-cyan-400 hover:from-emerald-400 hover:to-cyan-300 text-slate-950 font-black tracking-wide uppercase text-sm rounded-xl shadow-lg shadow-emerald-500/30 flex items-center gap-2 transform hover:-translate-y-0.5 transition-all focus:outline-none focus:ring-2 focus:ring-emerald-400"
                    >
                        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24" aria-hidden="true">
                            <polygon points="6 3 20 12 6 21 6 3" />
                        </svg>
                        Play Now Free
                    </Link>

                    <Link
                        v-if="game.category"
                        :href="route('categories.show', { slug: game.category.slug })"
                        class="px-5 py-3.5 bg-slate-900/80 hover:bg-slate-800 text-slate-200 hover:text-white border border-slate-700/80 font-semibold text-sm rounded-xl transition-all focus:outline-none focus:ring-2 focus:ring-slate-400"
                    >
                        More {{ game.category.name }}
                    </Link>
                </div>
            </div>

            <!-- Slide Switchers with compliant 44px+ touch targets -->
            <div v-if="games.length > 1" class="absolute bottom-6 right-6 sm:bottom-10 sm:right-10 z-20 flex items-center gap-1">
                <button
                    v-for="(_, dotIndex) in games"
                    :key="dotIndex"
                    @click="selectGame(dotIndex)"
                    class="p-2 flex items-center justify-center rounded-full transition-all focus:outline-none focus:ring-2 focus:ring-emerald-400"
                    :aria-label="`Slide ${dotIndex + 1}: ${games[dotIndex]?.title}`"
                    :title="`Slide ${dotIndex + 1}`"
                >
                    <span
                        class="h-2 rounded-full transition-all duration-300 block"
                        :class="dotIndex === activeIndex ? 'w-8 bg-emerald-400' : 'w-2.5 bg-slate-600 hover:bg-slate-400'"
                    ></span>
                </button>
            </div>
        </div>
    </div>
</template>
