<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import type { Category } from '@/types';

const props = defineProps<{
    category: Category;
}>();

// Map category slugs to theme colors and icons
const theme = computed(() => {
    switch (props.category.slug) {
        case 'action':
            return {
                bgAccent: 'group-hover:bg-rose-500/10 group-hover:border-rose-500/40',
                iconColor: 'text-rose-400',
                borderAccent: 'group-hover:border-rose-500/40',
                badgeBg: 'bg-rose-500/10 text-rose-300 border-rose-500/20',
            };
        case 'arcade':
            return {
                bgAccent: 'group-hover:bg-amber-500/10 group-hover:border-amber-500/40',
                iconColor: 'text-amber-400',
                borderAccent: 'group-hover:border-amber-500/40',
                badgeBg: 'bg-amber-500/10 text-amber-300 border-amber-500/20',
            };
        case 'puzzle':
            return {
                bgAccent: 'group-hover:bg-cyan-500/10 group-hover:border-cyan-500/40',
                iconColor: 'text-cyan-400',
                borderAccent: 'group-hover:border-cyan-500/40',
                badgeBg: 'bg-cyan-500/10 text-cyan-300 border-cyan-500/20',
            };
        case 'racing':
            return {
                bgAccent: 'group-hover:bg-emerald-500/10 group-hover:border-emerald-500/40',
                iconColor: 'text-emerald-400',
                borderAccent: 'group-hover:border-emerald-500/40',
                badgeBg: 'bg-emerald-500/10 text-emerald-300 border-emerald-500/20',
            };
        case 'adventure':
            return {
                bgAccent: 'group-hover:bg-indigo-500/10 group-hover:border-indigo-500/40',
                iconColor: 'text-indigo-400',
                borderAccent: 'group-hover:border-indigo-500/40',
                badgeBg: 'bg-indigo-500/10 text-indigo-300 border-indigo-500/20',
            };
        case 'sports':
            return {
                bgAccent: 'group-hover:bg-orange-500/10 group-hover:border-orange-500/40',
                iconColor: 'text-orange-400',
                borderAccent: 'group-hover:border-orange-500/40',
                badgeBg: 'bg-orange-500/10 text-orange-300 border-orange-500/20',
            };
        default:
            return {
                bgAccent: 'group-hover:bg-emerald-500/10 group-hover:border-emerald-500/40',
                iconColor: 'text-emerald-400',
                borderAccent: 'group-hover:border-emerald-500/40',
                badgeBg: 'bg-emerald-500/10 text-emerald-300 border-emerald-500/20',
            };
    }
});
</script>

<template>
    <Link
        :href="route('categories.show', { slug: category.slug })"
        class="group relative flex items-center gap-3 p-3 sm:p-3.5 rounded-2xl bg-slate-900/80 border border-slate-800/80 hover:bg-slate-900 hover:-translate-y-0.5 hover:shadow-lg transition-all duration-200 ease-out focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950"
        :class="theme.borderAccent"
    >
        <!-- Category Icon Box -->
        <div
            class="w-11 h-11 sm:w-12 sm:h-12 rounded-xl bg-slate-800/80 border border-slate-700/60 flex items-center justify-center shrink-0 group-hover:scale-105 transition-all duration-200"
            :class="[theme.iconColor, theme.bgAccent]"
        >
            <!-- Action (Lightning / Zap) -->
            <svg v-if="category.slug === 'action'" class="w-5 h-5 sm:w-6 sm:h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
            </svg>

            <!-- Arcade (Gamepad) -->
            <svg v-else-if="category.slug === 'arcade'" class="w-5 h-5 sm:w-6 sm:h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="6" x2="10" y1="12" y2="12"/><line x1="8" x2="8" y1="10" y2="14"/>
                <line x1="15" x2="15.01" y1="13" y2="13"/><line x1="18" x2="18.01" y1="11" y2="11"/>
                <rect width="20" height="12" x="2" y="6" rx="6"/>
            </svg>

            <!-- Puzzle (Puzzle Piece / Brain) -->
            <svg v-else-if="category.slug === 'puzzle'" class="w-5 h-5 sm:w-6 sm:h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19.439 7.85c-.049-.322.059-.648.289-.878l1.568-1.568a2.41 2.41 0 0 0 0-3.408l-.57-.57a2.41 2.41 0 0 0-3.408 0l-1.568 1.568a.93.93 0 0 1-.878.29c-.493-.074-.99-.074-1.484 0a.93.93 0 0 1-.878-.29L11.01 1.426a2.41 2.41 0 0 0-3.408 0l-.57.57a2.41 2.41 0 0 0 0 3.408l1.568 1.568c.23.23.338.556.29.878a8.04 8.04 0 0 0 0 1.484.93.93 0 0 1-.29.878L7.032 11.78a2.41 2.41 0 0 0 0 3.408l.57.57a2.41 2.41 0 0 0 3.408 0l1.568-1.568c.23-.23.556-.338.878-.29.493.074.99.074 1.484 0 .322.049.648-.059.878-.29l1.568-1.568a2.41 2.41 0 0 0 0-3.408l-.57-.57a2.41 2.41 0 0 0-3.408 0l-1.568 1.568a.93.93 0 0 1-.878.29"/>
            </svg>

            <!-- Racing (Gauge / Speed) -->
            <svg v-else-if="category.slug === 'racing'" class="w-5 h-5 sm:w-6 sm:h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m12 14 4-4"/><path d="M3.34 19a10 10 0 1 1 17.32 0"/>
            </svg>

            <!-- Adventure (Compass) -->
            <svg v-else-if="category.slug === 'adventure'" class="w-5 h-5 sm:w-6 sm:h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/>
            </svg>

            <!-- Sports (Trophy) -->
            <svg v-else-if="category.slug === 'sports'" class="w-5 h-5 sm:w-6 sm:h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/>
            </svg>

            <!-- Fallback Icon -->
            <svg v-else class="w-5 h-5 sm:w-6 sm:h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="6" width="20" height="12" rx="2"/>
                <path d="M6 12h4m-2-2v4m7-2h.01m3 0h.01"/>
            </svg>
        </div>

        <!-- Category Details -->
        <div class="flex flex-col min-w-0 flex-grow">
            <span class="font-bold text-xs sm:text-sm text-slate-100 group-hover:text-emerald-400 group-focus-visible:text-emerald-400 transition-colors truncate">
                {{ category.name }}
            </span>
            <span class="text-[11px] text-slate-400 font-medium">
                {{ category.games_count ?? 0 }} {{ category.games_count === 1 ? 'game' : 'games' }}
            </span>
        </div>

        <!-- Arrow indicator -->
        <svg
            class="w-3.5 h-3.5 text-slate-500 group-hover:text-emerald-400 group-hover:translate-x-0.5 transition-all shrink-0"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            aria-hidden="true"
        >
            <path d="m9 18 6-6-6-6"/>
        </svg>
    </Link>
</template>
