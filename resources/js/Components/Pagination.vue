<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { PaginationLink } from '@/types';

defineProps<{
    links: PaginationLink[];
}>();
</script>

<template>
    <div v-if="links.length > 3" class="flex flex-wrap items-center justify-center gap-1.5 mt-10">
        <template v-for="(link, key) in links" :key="key">
            <div
                v-if="link.url === null"
                class="px-3.5 py-2 text-xs font-medium text-slate-400 bg-slate-900/40 border border-slate-800/60 rounded-xl cursor-not-allowed select-none"
                v-html="link.label"
            />
            <Link
                v-else
                :href="link.url"
                preserve-scroll
                class="px-3.5 py-2 text-xs font-semibold rounded-xl border transition-all focus:outline-none"
                :class="
                    link.active
                        ? 'bg-emerald-500 text-slate-950 border-emerald-400 font-bold shadow-md shadow-emerald-500/20'
                        : 'bg-slate-900/80 hover:bg-slate-800 text-slate-300 hover:text-white border-slate-800 hover:border-slate-700'
                "
                v-html="link.label"
            />
        </template>
    </div>
</template>

