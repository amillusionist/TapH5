<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    compact?: boolean;
}>();
</script>

<template>
    <Link href="/" class="flex items-center gap-2.5 group focus:outline-none focus:ring-2 focus:ring-emerald-400 rounded-xl" aria-label="TapH5 Games Homepage">
        <div class="relative flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 via-teal-500 to-cyan-500 p-0.5 shadow-lg shadow-emerald-500/20 group-hover:shadow-emerald-500/40 transition-shadow">
            <div class="w-full h-full bg-slate-950 rounded-[10px] flex items-center justify-center">
                <svg class="w-5 h-5 text-emerald-400 group-hover:scale-110 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="6 3 20 12 6 21 6 3" fill="currentColor" fill-opacity="0.2"/>
                </svg>
            </div>
            <!-- Glow dot -->
            <span class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 bg-emerald-400 rounded-full ring-2 ring-slate-950 animate-pulse"></span>
        </div>

        <div v-if="!compact" class="flex flex-col">
            <div class="flex items-center gap-1">
                <span class="text-xl font-black tracking-tight text-white font-sans">
                    TAP<span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 via-teal-300 to-cyan-400">H5</span>
                </span>
                <span class="text-[10px] font-bold uppercase tracking-widest px-1.5 py-0.5 bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 rounded">GAMES</span>
            </div>
            <span class="text-[10px] font-medium text-slate-400 -mt-1 tracking-wider uppercase">Free Web Gaming</span>
        </div>
    </Link>
</template>

