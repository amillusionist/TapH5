<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { Game } from '@/types';

defineProps<{
    game: Game;
    compact?: boolean;
}>();
</script>

<template>
    <Link
        :href="route('games.show', { slug: game.slug })"
        class="group relative flex flex-col overflow-hidden rounded-2xl bg-slate-900/80 border border-slate-800/80 hover:border-emerald-500/50 hover:shadow-glow-emerald transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-emerald-500/50"
    >
        <!-- Thumbnail Container -->
        <div class="relative w-full aspect-[4/3] overflow-hidden bg-slate-950">
            <img
                :src="game.thumbnail || '/images/thumbnails/neon-pulse.svg'"
                :alt="game.title"
                loading="lazy"
                class="w-full h-full object-cover object-center transform group-hover:scale-105 transition-transform duration-500 ease-out"
            />

            <!-- Gradient overlay for text contrast -->
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/20 to-transparent opacity-60 group-hover:opacity-40 transition-opacity"></div>

            <!-- Badges -->
            <div class="absolute top-2.5 left-2.5 flex flex-wrap gap-1.5 z-10">
                <span
                    v-if="game.category"
                    class="px-2 py-0.5 text-[11px] font-semibold tracking-wide uppercase bg-slate-950/80 backdrop-blur-md text-emerald-400 border border-emerald-500/30 rounded-full"
                >
                    {{ game.category.name }}
                </span>
                <span
                    v-if="game.is_featured"
                    class="px-2 py-0.5 text-[11px] font-bold tracking-wide uppercase bg-amber-500/90 text-slate-950 rounded-full shadow-sm"
                >
                    Featured
                </span>
            </div>

            <!-- Hover Play Icon Button -->
            <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-300 transform scale-90 group-hover:scale-100">
                <div class="w-12 h-12 rounded-full bg-emerald-500 text-slate-950 flex items-center justify-center shadow-lg shadow-emerald-500/50">
                    <svg class="w-6 h-6 ml-0.5" viewBox="0 0 24 24" fill="currentColor">
                        <polygon points="6 3 20 12 6 21 6 3" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Meta Info -->
        <div class="flex flex-col p-3.5 bg-slate-900/90">
            <h3 class="font-bold text-sm sm:text-base text-slate-100 group-hover:text-emerald-400 transition-colors line-clamp-1">
                {{ game.title }}
            </h3>

            <p v-if="!compact && game.description" class="mt-1 text-xs text-slate-400 line-clamp-2 leading-relaxed">
                {{ game.description }}
            </p>

            <div class="mt-2.5 flex items-center justify-between text-[11px] text-slate-400 pt-2 border-t border-slate-800/60">
                <span class="inline-flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                    <span>Free Play</span>
                </span>
                <span class="capitalize text-slate-400 font-medium">
                    {{ game.game_type === 'multi_file' ? 'Multi-File' : 'Instant H5' }}
                </span>
            </div>
        </div>
    </Link>
</template>

