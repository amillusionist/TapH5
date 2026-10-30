<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

withDefaults(
    defineProps<{
        title?: string;
        message?: string;
        actionText?: string;
        actionHref?: string;
    }>(),
    {
        title: 'No Games Found',
        message: 'We could not find any games matching your current criteria.',
        actionText: 'Browse All Games',
        actionHref: '/games',
    }
);
</script>

<template>
    <div class="flex flex-col items-center justify-center p-12 text-center rounded-3xl bg-slate-900/40 border border-slate-800/80 my-8">
        <div class="w-16 h-16 rounded-2xl bg-slate-800/60 border border-slate-700/60 flex items-center justify-center text-slate-400 mb-4">
            <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="2" y="6" width="20" height="12" rx="2"/>
                <path d="M6 12h4m-2-2v4m7-2h.01m3 0h.01"/>
            </svg>
        </div>

        <h3 class="text-lg font-bold text-slate-100 mb-1.5">{{ title }}</h3>
        <p class="text-xs sm:text-sm text-slate-400 max-w-sm mb-6 leading-relaxed">{{ message }}</p>

        <Link
            v-if="actionHref && actionText"
            :href="actionHref"
            class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-emerald-500 hover:text-slate-950 text-slate-200 text-xs font-bold uppercase tracking-wider border border-slate-700 transition-all focus:outline-none"
        >
            {{ actionText }}
        </Link>
    </div>
</template>

