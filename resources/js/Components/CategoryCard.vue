<script setup lang="ts">
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import type { Category } from '@/types';

defineProps<{
    category: Category;
}>();

const imageError = ref(false);
</script>

<template>
    <Link
        :href="route('categories.show', { slug: category.slug })"
        class="group relative flex items-center gap-3.5 p-3.5 rounded-2xl bg-slate-900/60 border border-slate-800 hover:border-emerald-500/50 hover:bg-slate-900/90 hover:shadow-glow-emerald transition-all duration-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950"
    >
        <div class="w-12 h-12 rounded-xl bg-slate-800/80 border border-slate-700/60 flex items-center justify-center text-emerald-400 group-hover:scale-105 group-hover:border-emerald-500/40 transition-all overflow-hidden shrink-0">
            <img
                v-if="category.thumbnail && !imageError"
                :src="category.thumbnail"
                :alt="`${category.name} category thumbnail`"
                width="48"
                height="48"
                class="w-full h-full object-cover"
                loading="lazy"
                decoding="async"
                @error="imageError = true"
            />
            <svg v-else class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <rect x="2" y="6" width="20" height="12" rx="2"/>
                <path d="M6 12h4m-2-2v4m7-2h.01m3 0h.01"/>
            </svg>
        </div>

        <div class="flex flex-col min-w-0">
            <span class="font-bold text-sm text-slate-100 group-hover:text-emerald-400 group-focus-visible:text-emerald-400 transition-colors truncate">
                {{ category.name }}
            </span>
            <span class="text-xs text-slate-400">
                {{ category.games_count ?? 0 }} {{ category.games_count === 1 ? 'Game' : 'Games' }}
            </span>
        </div>
    </Link>
</template>
